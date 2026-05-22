-- Migration: Create parish_handovers table
CREATE TABLE IF NOT EXISTS parish_handovers (
    handover_id INTEGER PRIMARY KEY AUTOINCREMENT,
    parish_id INTEGER NOT NULL,
    outgoing_priest_id INTEGER,
    incoming_priest_id INTEGER,
    assignment_id INTEGER,
    status TEXT DEFAULT 'Pending', -- Pending, Signed_Off, Accepted, Completed
    registry_status_notes TEXT,
    outgoing_sign_date DATETIME,
    incoming_accept_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parish_id) REFERENCES parishes(parish_id),
    FOREIGN KEY (outgoing_priest_id) REFERENCES users(user_id),
    FOREIGN KEY (incoming_priest_id) REFERENCES users(user_id)
);
