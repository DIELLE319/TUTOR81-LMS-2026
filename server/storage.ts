import { db } from "./db";
import { users } from "@shared/models/auth";
import { eq } from "drizzle-orm";
import type { UpsertUser } from "@shared/models/auth";

export interface IAuthStorage {
  getUser(id: string): Promise<any | undefined>;
  getUserByEmail(email: string): Promise<any | undefined>;
  upsertUser(user: UpsertUser): Promise<any>;
}

export const authStorage: IAuthStorage = {
  async getUser(id: string) {
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user;
  },

  async getUserByEmail(email: string) {
    const [user] = await db.select().from(users).where(eq(users.email, email));
    return user;
  },

  async upsertUser(userData: UpsertUser) {
    const [existing] = await db
      .select()
      .from(users)
      .where(eq(users.id, userData.id!));

    if (existing) {
      const [updated] = await db
        .update(users)
        .set({
          email: userData.email,
          firstName: userData.firstName,
          lastName: userData.lastName,
          profileImageUrl: userData.profileImageUrl,
          updatedAt: new Date(),
        })
        .where(eq(users.id, userData.id!))
        .returning();
      return updated;
    }

    const [created] = await db.insert(users).values(userData).returning();
    return created;
  },
};
