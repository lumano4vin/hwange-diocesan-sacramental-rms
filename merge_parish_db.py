import sqlite3
import os
import sys

def merge_databases(master_db, incoming_db):
    if not os.path.exists(master_db) or not os.path.exists(incoming_db):
        print("Error: Database files not found.")
        return

    conn_master = sqlite3.connect(master_db)
    conn_incoming = sqlite3.connect(incoming_db)
    
    cm = conn_master.cursor()
    ci = conn_incoming.cursor()

    print(f"Merging {incoming_db} into {master_db}...\n")

    # Dictionary to hold old person_id -> new person_id mappings
    person_id_map = {}

    # 1. MERGE PARISHIONERS
    ci.execute("SELECT person_id, first_name, last_name, other_names, gender, dob, place_of_birth, father_name, mother_name, mother_maiden_name, current_parish_id, title, status FROM parishioners")
    incoming_parishioners = ci.fetchall()

    for p in incoming_parishioners:
        old_id = p[0]
        first_name = p[1]
        last_name = p[2]
        dob = p[5]

        # Check if person already exists in master (matching by name and DOB)
        cm.execute("SELECT person_id FROM parishioners WHERE first_name=? AND last_name=? AND dob=?", (first_name, last_name, dob))
        existing = cm.fetchone()

        if existing:
            person_id_map[old_id] = existing[0]
            print(f"[Skip] Parishioner already exists: {first_name} {last_name}")
        else:
            # Insert new parishioner
            cm.execute("""INSERT INTO parishioners 
                          (first_name, last_name, other_names, gender, dob, place_of_birth, father_name, mother_name, mother_maiden_name, current_parish_id, title, status)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?,?)""", p[1:])
            new_id = cm.lastrowid
            person_id_map[old_id] = new_id
            print(f"[Added] Parishioner: {first_name} {last_name}")

    # 2. MERGE BAPTISMS (using the mapped IDs to prevent foreign key corruption)
    ci.execute("SELECT baptism_id, person_id, parish_id, date_of_baptism, minister, godparents, witnesses, register_book_number, page_number, entry_number, verification_hash, status FROM baptisms")
    incoming_baptisms = ci.fetchall()

    for b in incoming_baptisms:
        old_person_id = b[1]
        new_person_id = person_id_map.get(old_person_id)

        if not new_person_id:
            continue # Skip if person was somehow not mapped

        # Check if baptism already exists for this person in master
        cm.execute("SELECT baptism_id FROM baptisms WHERE person_id=?", (new_person_id,))
        if cm.fetchone():
            continue

        new_b = list(b)
        new_b[1] = new_person_id # Replace the old foreign key with the new mapped one
        cm.execute("""INSERT INTO baptisms 
                      (person_id, parish_id, date_of_baptism, minister, godparents, witnesses, register_book_number, page_number, entry_number, verification_hash, status)
                      VALUES (?,?,?,?,?,?,?,?,?,?,?)""", new_b[1:])
        print(f"[Added] Baptism record mapped to Person ID: {new_person_id}")

    # (Additional tables like Marriages, Confirmations would follow the same ID mapping pattern here)

    # Commit changes
    conn_master.commit()
    conn_master.close()
    conn_incoming.close()
    print("\nMerge completed successfully.")

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python merge_parish_db.py <master.sqlite> <incoming.sqlite>")
        print("Example: python merge_parish_db.py database.sqlite incoming_st_mary.sqlite")
    else:
        merge_databases(sys.argv[1], sys.argv[2])
