import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, desc, and, sql } from "drizzle-orm";
import { getAuthenticatedDbUser } from "./helpers";
import crypto from "crypto";

function generateRoomSlug(): string {
  const words = ["tutor81", "meet", "sala", "aula", "conf"];
  const pick = words[Math.floor(Math.random() * words.length)];
  const hex = crypto.randomBytes(4).toString("hex");
  return `${pick}-${hex}`;
}

export function registerVideoRoomsRoutes(app: Express) {
  // List rooms for this tutor (or all for super admin)
  app.get("/api/video-rooms", isAuthenticated, async (req: any, res) => {
    try {
      if (!hasDatabase) return res.json([]);
      const dbUser = await getAuthenticatedDbUser(req);
      const role = (dbUser?.role as number) ?? 0;
      const tutorId = dbUser?.tutor_id as number | null;

      const rows = await db.select({
        id: schema.videoRooms.id,
        tutorId: schema.videoRooms.tutorId,
        createdBy: schema.videoRooms.createdBy,
        roomName: schema.videoRooms.roomName,
        jitsiRoomId: schema.videoRooms.jitsiRoomId,
        description: schema.videoRooms.description,
        scheduledAt: schema.videoRooms.scheduledAt,
        duration: schema.videoRooms.duration,
        participantEmails: schema.videoRooms.participantEmails,
        status: schema.videoRooms.status,
        createdAt: schema.videoRooms.createdAt,
        tutorName: schema.tutors.businessName,
      }).from(schema.videoRooms)
        .leftJoin(schema.tutors, eq(schema.videoRooms.tutorId, schema.tutors.id))
        .where(role >= 1000 ? sql`1=1` : tutorId ? eq(schema.videoRooms.tutorId, tutorId) : sql`1=0`)
        .orderBy(desc(schema.videoRooms.scheduledAt));

      res.json(rows);
    } catch (error) {
      console.error("Error fetching video rooms:", error);
      res.status(500).json({ error: "Failed to fetch rooms" });
    }
  });

  // Create room
  app.post("/api/video-rooms", isAuthenticated, async (req: any, res) => {
    try {
      const dbUser = await getAuthenticatedDbUser(req);
      const tutorId = dbUser?.tutor_id as number | null;
      const { roomName, description, scheduledAt, duration, participantEmails } = req.body;

      if (!roomName) return res.status(400).json({ error: "Nome stanza obbligatorio" });

      const jitsiRoomId = generateRoomSlug();

      const [room] = await db.insert(schema.videoRooms).values({
        tutorId: tutorId || undefined,
        createdBy: dbUser?.id || undefined,
        roomName,
        jitsiRoomId,
        description: description || null,
        scheduledAt: scheduledAt ? new Date(scheduledAt) : null,
        duration: duration || 60,
        participantEmails: participantEmails || null,
        status: "scheduled",
      }).returning();

      res.json(room);
    } catch (error) {
      console.error("Error creating video room:", error);
      res.status(500).json({ error: "Failed to create room" });
    }
  });

  // Update room status
  app.patch("/api/video-rooms/:id", isAuthenticated, async (req: any, res) => {
    try {
      const id = parseInt(req.params.id);
      const { status, roomName, description, scheduledAt, duration, participantEmails } = req.body;

      const updates: any = {};
      if (status !== undefined) updates.status = status;
      if (roomName !== undefined) updates.roomName = roomName;
      if (description !== undefined) updates.description = description;
      if (scheduledAt !== undefined) updates.scheduledAt = scheduledAt ? new Date(scheduledAt) : null;
      if (duration !== undefined) updates.duration = duration;
      if (participantEmails !== undefined) updates.participantEmails = participantEmails;

      const [room] = await db.update(schema.videoRooms)
        .set(updates)
        .where(eq(schema.videoRooms.id, id))
        .returning();

      res.json(room);
    } catch (error) {
      console.error("Error updating video room:", error);
      res.status(500).json({ error: "Failed to update room" });
    }
  });

  // Delete room
  app.delete("/api/video-rooms/:id", isAuthenticated, async (req: any, res) => {
    try {
      const id = parseInt(req.params.id);
      await db.delete(schema.videoRooms).where(eq(schema.videoRooms.id, id));
      res.json({ ok: true });
    } catch (error) {
      console.error("Error deleting video room:", error);
      res.status(500).json({ error: "Failed to delete room" });
    }
  });
}
