-- Sequence and defined type for users table
CREATE SEQUENCE IF NOT EXISTS users_id_seq;

-- Table Definition for users table
CREATE TABLE "public"."users" (
                                  "id" int4 NOT NULL DEFAULT nextval('users_id_seq'::regclass),
                                  "username" varchar(25),
                                  "password" varchar NOT NULL,
                                  "email" varchar(255) NOT NULL,
                                  "image" varchar(255),
                                  "status" varchar(255),
                                  "verification_code" varchar(255) DEFAULT NULL::character varying,
                                  "is_verified" bool DEFAULT false,
                                  PRIMARY KEY ("id")
);

-- Indices for users table
CREATE UNIQUE INDEX users_username_key ON public.users USING btree (username);
CREATE UNIQUE INDEX users_email_key ON public.users USING btree (email);

-- Sequence and defined type for chatrooms table
CREATE SEQUENCE IF NOT EXISTS chatrooms_id_seq;

-- Table Definition for chatrooms table
CREATE TABLE "public"."chatrooms" (
                                      "id" int4 NOT NULL DEFAULT nextval('chatrooms_id_seq'::regclass),
                                      "name" varchar(255) NOT NULL,
                                      "owner_id" int4,
                                      "created_at" timestamp DEFAULT CURRENT_TIMESTAMP,
                                      CONSTRAINT "chatrooms_owner_id_fkey" FOREIGN KEY ("owner_id") REFERENCES "public"."users"("id"),
                                      PRIMARY KEY ("id")
);

-- Sequence and defined type for chatroom_messages table
CREATE SEQUENCE IF NOT EXISTS chatroom_messages_id_seq;

-- Table Definition for chatroom_messages table
CREATE TABLE "public"."chatroom_messages" (
                                              "id" int4 NOT NULL DEFAULT nextval('chatroom_messages_id_seq'::regclass),
                                              "chatroom_id" int4,
                                              "user_id" int4,
                                              "message" text NOT NULL,
                                              "sent_at" timestamp DEFAULT CURRENT_TIMESTAMP,
                                              CONSTRAINT "chatroom_messages_chatroom_id_fkey" FOREIGN KEY ("chatroom_id") REFERENCES "public"."chatrooms"("id"),
                                              CONSTRAINT "chatroom_messages_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id"),
                                              PRIMARY KEY ("id")
);

-- Sequence and defined type for chatroom_users table
CREATE SEQUENCE IF NOT EXISTS chatroom_users_id_seq;

-- Table Definition for chatroom_users table
CREATE TABLE "public"."chatroom_users" (
                                           "id" int4 NOT NULL DEFAULT nextval('chatroom_users_id_seq'::regclass),
                                           "chatroom_id" int4,
                                           "user_id" int4,
                                           "is_admin" bool DEFAULT false,
                                           "joined_at" timestamp DEFAULT CURRENT_TIMESTAMP,
                                           "pending_approval" bool DEFAULT false,
                                           CONSTRAINT "chatroom_users_chatroom_id_fkey" FOREIGN KEY ("chatroom_id") REFERENCES "public"."chatrooms"("id"),
                                           CONSTRAINT "chatroom_users_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id"),
                                           PRIMARY KEY ("id")
);

-- Indices for chatroom_users table
CREATE UNIQUE INDEX chatroom_users_chatroom_id_user_id_key ON public.chatroom_users USING btree (chatroom_id, user_id);

-- Sequence and defined type for chatroom_message_reactions table
CREATE SEQUENCE IF NOT EXISTS chatroom_message_reactions_id_seq;

-- Table Definition for chatroom_message_reactions table
CREATE TABLE "public"."chatroom_message_reactions" (
                                                       "chatroom_message_id" int4 NOT NULL,
                                                       "user_id" int4 NOT NULL,
                                                       "reaction_type" int4,
                                                       CONSTRAINT "chatroom_message_reactions_chatroom_message_id_fkey" FOREIGN KEY ("chatroom_message_id") REFERENCES "public"."chatroom_messages"("id"),
                                                       CONSTRAINT "chatroom_message_reactions_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id"),
                                                       PRIMARY KEY ("chatroom_message_id","user_id")
);

-- Sequence and defined type for invite_requests table
CREATE SEQUENCE IF NOT EXISTS invite_requests_id_seq;

-- Table Definition for invite_requests table
CREATE TABLE "public"."invite_requests" (
                                            "id" int4 NOT NULL DEFAULT nextval('invite_requests_id_seq'::regclass),
                                            "chatroom_id" int4,
                                            "inviter_id" int4,
                                            "invitee_id" int4,
                                            "status" varchar(20) DEFAULT 'pending'::character varying,
                                            "created_at" timestamp DEFAULT CURRENT_TIMESTAMP,
                                            CONSTRAINT "invite_requests_chatroom_id_fkey" FOREIGN KEY ("chatroom_id") REFERENCES "public"."chatrooms"("id"),
                                            CONSTRAINT "invite_requests_inviter_id_fkey" FOREIGN KEY ("inviter_id") REFERENCES "public"."users"("id"),
                                            CONSTRAINT "invite_requests_invitee_id_fkey" FOREIGN KEY ("invitee_id") REFERENCES "public"."users"("id"),
                                            PRIMARY KEY ("id")
);
CREATE SEQUENCE IF NOT EXISTS messages_id_seq;

-- Table Definition for messages table
CREATE TABLE "public"."messages" (
                                     "id" int8 NOT NULL DEFAULT nextval('messages_id_seq'::regclass),
                                     "sender" text NOT NULL,
                                     "recipient" text NOT NULL,
                                     "message" text NOT NULL,
                                     "timestamp" timestamp DEFAULT CURRENT_TIMESTAMP,
                                     PRIMARY KEY ("id")
);
-- Sequence and defined type for message_reactions table
CREATE SEQUENCE IF NOT EXISTS message_reactions_id_seq;

-- Table Definition for message_reactions table
CREATE TABLE "public"."message_reactions" (
                                              "id" int4 NOT NULL DEFAULT nextval('message_reactions_id_seq'::regclass),
                                              "message_id" int4 NOT NULL,
                                              "user_id" int4 NOT NULL,
                                              "created_at" timestamp DEFAULT CURRENT_TIMESTAMP,
                                              "reaction_type" int2 NOT NULL,
                                              CONSTRAINT "message_reactions_user_id_fkey" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id"),
                                              CONSTRAINT "message_reactions_message_id_fkey" FOREIGN KEY ("message_id") REFERENCES "public"."messages"("id"),
                                              PRIMARY KEY ("id")
);

-- Indices for message_reactions table
CREATE UNIQUE INDEX unique_message_user ON public.message_reactions USING btree (message_id, user_id);

-- Sequence and defined type for messages table

-- Sequence and defined type for suggested_users table
CREATE SEQUENCE IF NOT EXISTS suggested_users_id_seq;

-- Table Definition for suggested_users table
CREATE TABLE "public"."suggested_users" (
                                            "id" int4 NOT NULL DEFAULT nextval('suggested_users_id_seq'::regclass),
                                            "chatroom_id" int4 NOT NULL,
                                            "suggested_by_user_id" int4,
                                            "suggested_user_id" int4 NOT NULL,
                                            "status" varchar(20) NOT NULL DEFAULT 'pending'::character varying,
                                            CONSTRAINT "suggested_users_chatroom_id_fkey" FOREIGN KEY ("chatroom_id") REFERENCES "public"."chatrooms"("id"),
                                            CONSTRAINT "suggested_users_suggested_by_user_id_fkey" FOREIGN KEY ("suggested_by_user_id") REFERENCES "public"."users"("id"),
                                            CONSTRAINT "suggested_users_suggested_user_id_fkey" FOREIGN KEY ("suggested_user_id") REFERENCES "public"."users"("id"),
                                            PRIMARY KEY ("id")
);

-- Sequence and defined type for unverified_users table
CREATE SEQUENCE IF NOT EXISTS unverified_users_id_seq;

-- Table Definition for unverified_users table
CREATE TABLE "public"."unverified_users" (
                                             "id" int4 NOT NULL DEFAULT nextval('unverified_users_id_seq'::regclass),
                                             "username" varchar(255) NOT NULL,
                                             "email" varchar(255) NOT NULL,
                                             "password" varchar(255) NOT NULL,
                                             "image" varchar(255),
                                             "verification_code" varchar(32) NOT NULL,
                                             "created_at" timestamp DEFAULT CURRENT_TIMESTAMP,
                                             PRIMARY KEY ("id")
);

-- Indices for unverified_users table
CREATE UNIQUE INDEX unverified_users_username_key ON public.unverified_users USING btree (username);
CREATE UNIQUE INDEX unverified_users_email_key ON public.unverified_users USING btree (email);
