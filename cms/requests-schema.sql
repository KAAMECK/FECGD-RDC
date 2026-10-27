CREATE TABLE IF NOT EXISTS request_meta(request_id INTEGER PRIMARY KEY REFERENCES requests(id) ON DELETE CASCADE,is_read INTEGER NOT NULL DEFAULT 0,priority TEXT NOT NULL DEFAULT 'normal',archived INTEGER NOT NULL DEFAULT 0,note TEXT NOT NULL DEFAULT '',version INTEGER NOT NULL DEFAULT 0,updated_at TEXT NOT NULL DEFAULT '');
CREATE TABLE IF NOT EXISTS request_events(id INTEGER PRIMARY KEY,request_id INTEGER NOT NULL REFERENCES requests(id) ON DELETE CASCADE,actor TEXT NOT NULL,description TEXT NOT NULL,created_at TEXT NOT NULL);
CREATE INDEX IF NOT EXISTS request_events_request ON request_events(request_id,id);
CREATE INDEX IF NOT EXISTS requests_inbox ON requests(kind,status,created_at);
