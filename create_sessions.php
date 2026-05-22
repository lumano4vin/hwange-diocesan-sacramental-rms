<?php
require_once 'includes/db.php';
run_schema_migrations($pdo);
echo "Database migrations completed successfully!";
