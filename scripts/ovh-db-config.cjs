function getOvhDbConfig() {
  const host = process.env.OVH_DB_HOST;
  const port = Number.parseInt(process.env.OVH_DB_PORT || "3306", 10);
  const user = process.env.OVH_DB_USER;
  const password = process.env.OVH_DB_PASSWORD;
  const database = process.env.OVH_DB_NAME;
  const connectTimeout = Number.parseInt(process.env.OVH_DB_CONNECT_TIMEOUT || "10000", 10);

  const missing = [];
  if (!host) missing.push("OVH_DB_HOST");
  if (!user) missing.push("OVH_DB_USER");
  if (!password) missing.push("OVH_DB_PASSWORD");
  if (!database) missing.push("OVH_DB_NAME");

  if (missing.length) {
    throw new Error(`Missing OVH DB env vars: ${missing.join(", ")}`);
  }

  return { host, port, user, password, database, connectTimeout };
}

module.exports = { getOvhDbConfig };
