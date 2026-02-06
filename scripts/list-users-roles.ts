import { db, hasDatabase } from "../server/db";
import { users } from "@shared/models/auth";
import { asc, sql } from "drizzle-orm";

type Role = 0 | 1 | 2 | 1000;

type OutputFormat = "tsv" | "csv" | "json";

function parseArgs(argv: string[]): { format: OutputFormat } {
  const formatArg = argv.find((a) => a.startsWith("--format="));
  const formatValue = formatArg?.split("=")[1]?.trim().toLowerCase();

  if (argv.includes("--csv")) return { format: "csv" };
  if (argv.includes("--tsv")) return { format: "tsv" };
  if (argv.includes("--json")) return { format: "json" };
  if (formatValue === "csv" || formatValue === "tsv" || formatValue === "json") {
    return { format: formatValue };
  }
  return { format: "tsv" };
}

function escapeCsvCell(value: string): string {
  const needsQuotes = /[\n\r\t,\"]/g.test(value);
  const escaped = value.replace(/\"/g, '""');
  return needsQuotes ? `"${escaped}"` : escaped;
}

function roleLabel(role: number | null | undefined): string {
  switch (role) {
    case 1000:
      return "Super Admin";
    case 1:
      return "Venditore (Ente)";
    case 2:
      return "Referente Azienda";
    case 0:
      return "Corsista";
    default:
      return `Legacy/Sconosciuto (${role ?? "null"})`;
  }
}

function asRole(value: number | null | undefined): Role | null {
  if (value === 0 || value === 1 || value === 2 || value === 1000) return value;
  return null;
}

async function main() {
  const { format } = parseArgs(process.argv.slice(2));

  if (!hasDatabase) {
    console.error("DATABASE_URL mancante: impossibile leggere gli utenti.");
    process.exit(1);
  }

  const allUsers = await db
    .select({
      id: users.id,
      email: users.email,
      firstName: users.firstName,
      lastName: users.lastName,
      role: users.role,
      idcompany: users.idcompany,
      createdAt: users.createdAt,
      updatedAt: users.updatedAt,
    })
    .from(users)
    .orderBy(asc(users.email));

  const counts = new Map<string, number>();
  for (const u of allUsers) {
    const key = roleLabel(u.role);
    counts.set(key, (counts.get(key) ?? 0) + 1);
  }

  if (format === "tsv") {
    console.log("\n=== Utenti (Replit Auth) ===");
    console.log(`Totale: ${allUsers.length}`);
    console.log("\n--- Conteggio per ruolo ---");
    for (const [k, v] of [...counts.entries()].sort((a, b) => b[1] - a[1])) {
      console.log(`${String(v).padStart(4, " ")}  ${k}`);
    }
    console.log("\n--- Elenco utenti ---");
  }

  const header = [
    "email",
    "nome",
    "role",
    "label",
    "idcompany",
    "tutorId",
    "tutorName",
    "createdAt",
  ];

  if (format === "csv") {
    console.log(header.map(escapeCsvCell).join(","));
  } else if (format === "tsv") {
    console.log(header.join("\t"));
  }

  const rowsOut: Array<Record<string, unknown>> = [];

  for (const u of allUsers) {
    let tutorId: number | null = null;
    let tutorName: string | null = null;

    if (asRole(u.role) === 1 && u.idcompany) {
      try {
        const result = await db.execute(
          sql`
            SELECT ta.tutor_id, t.business_name as tutor_name
            FROM tutor_admins ta
            JOIN tutors t ON t.id = ta.tutor_id
            WHERE ta.id = ${u.idcompany}
            LIMIT 1
          `,
        );
        if (result.rows.length > 0) {
          tutorId = Number((result.rows[0] as any).tutor_id);
          tutorName = String((result.rows[0] as any).tutor_name ?? "");
        }
      } catch {
        // ignore if tutor tables are missing in a given env
      }
    }

    const fullName = [u.firstName, u.lastName].filter(Boolean).join(" ").trim();
    const values = [
      u.email ?? "",
      fullName,
      String(u.role ?? ""),
      roleLabel(u.role),
      String(u.idcompany ?? ""),
      tutorId ? String(tutorId) : "",
      tutorName ?? "",
      u.createdAt ? new Date(u.createdAt).toISOString() : "",
    ];

    if (format === "csv") {
      console.log(values.map(escapeCsvCell).join(","));
    } else if (format === "tsv") {
      console.log(values.join("\t"));
    } else {
      rowsOut.push({
        email: values[0],
        name: values[1],
        role: u.role,
        roleLabel: values[3],
        idcompany: u.idcompany,
        tutorId,
        tutorName,
        createdAt: values[7],
      });
    }
  }

  if (format === "json") {
    console.log(
      JSON.stringify(
        {
          total: allUsers.length,
          counts: Object.fromEntries(counts.entries()),
          users: rowsOut,
        },
        null,
        2,
      ),
    );
  }

  const legacy = allUsers.filter((u) => asRole(u.role) === null);
  if (legacy.length) {
    console.log("\n--- ATTENZIONE: ruoli legacy trovati ---");
    for (const u of legacy) {
      console.log(`${u.email ?? u.id}: role=${u.role}`);
    }
    process.exitCode = 2;
  }
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
