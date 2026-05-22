import sqlite3

parishes_data = [
    ('St. Ignatius Cathedral', 'Hwange', 'Hwange Urban'),
    ('Holy Family', 'Hwange', 'Hwange Urban'),
    ('Our Lady of Peace', 'Hwange', 'Hwange Urban'),
    ('Mary Help of Christians', 'Don Bosco, Hwange', 'Hwange Urban'),
    ('Ss. Peter and Paul', 'Hwange', 'Hwange Urban'),
    ('St. Charles Lwanga', 'Hwange', 'Hwange Urban'),
    ('St. Francis Xavier', 'Dete', 'Dete'),
    ('St. Francis of Assisi', 'Cross Dete', 'Dete'),
    ('St. George', 'Hwange', 'Hwange Urban'),
    ('St. Joachim', 'Hwange', 'Hwange Urban'),
    ('St. Joseph', 'Hwange', 'Hwange Urban'),
    ('St. Josephine Bakhita', 'Victoria Falls', 'Victoria Falls'),
    ('St. Kizito', 'Hwange', 'Hwange Urban'),
    ('St. Monica', 'Hwange', 'Hwange Urban'),
    ('St. Teresa', 'Hwange', 'Hwange Urban'),
    ('All Souls', 'Binga', 'Binga'),
    ('Divine Mercy', 'Lubimbi', 'Binga'),
    ('Holy Cross', 'Lusulu', 'Binga'),
    ('Mary Immaculate', 'Gomoza', 'Lupane'),
    ('Our Lady of Fatima', 'Fatima', 'Lupane'),
    ('Sacred Heart', 'Jambezi', 'Jambezi'),
    ('St. Augustine', 'Mzola', 'Lupane'),
    ('St. Cecilia', 'Tshongokwe', 'Lupane'),
    ('St. Faustine', 'Matetsi', 'Victoria Falls'),
    ('St. John the Baptist (Dandanda)', 'Dandanda', 'Lupane'),
    ('St. John the Baptist (Makwa)', 'Makwa', 'Makwa'),
    ('St. John Vianney', 'Kariangwe', 'Binga'),
    ('St. Luke', 'Chisuma', 'Victoria Falls'),
    ('St. Mathew', 'Dambwamkulu', 'Binga'),
    ('St. Mark', 'Nagangala', 'Binga'),
    ('St. Martin de Porres', 'Jotsholo', 'Lupane'),
    ('St. Mary', 'Lukosi', 'Hwange Urban'),
    ('St. Michael', 'Kasibo', 'Hwange Urban'),
    ('St. Padre Pio', 'Siacilaba', 'Binga'),
    ('St. Therese', 'Kamativi', 'Dete')
]

db_file = 'database.sqlite'

def sync():
    conn = sqlite3.connect(db_file)
    cursor = conn.cursor()
    
    try:
        # Clear existing
        cursor.execute("DELETE FROM parishes")
        cursor.execute("DELETE FROM sqlite_sequence WHERE name='parishes'")
        
        # Insert
        cursor.executemany("INSERT INTO parishes (parish_name, location, deanery) VALUES (?, ?, ?)", parishes_data)
        
        conn.commit()
        print(f"Success: Synced {len(parishes_data)} parishes.")
    except Exception as e:
        print(f"Error: {e}")
    finally:
        conn.close()

if __name__ == "__main__":
    sync()
