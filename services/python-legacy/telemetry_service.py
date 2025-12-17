#!/usr/bin/env python3
"""
Telemetry Legacy Service - Python rewrite
Generates CSV telemetry data and inserts into PostgreSQL
"""

import csv
import logging
import os
import random
import sys
import time
from datetime import datetime
from pathlib import Path

import psycopg2
from psycopg2 import sql

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger('telemetry-legacy')


class Config:
    """Configuration from environment variables"""
    def __init__(self):
        self.csv_out_dir = os.getenv('CSV_OUT_DIR', '/data/csv')
        self.gen_period_sec = int(os.getenv('GEN_PERIOD_SEC', '30'))
        self.pghost = os.getenv('PGHOST', 'db')
        self.pgport = int(os.getenv('PGPORT', '5432'))
        self.pguser = os.getenv('PGUSER', 'monouser')
        self.pgpassword = os.getenv('PGPASSWORD', 'monopass')
        self.pgdatabase = os.getenv('PGDATABASE', 'monolith')


class TelemetryGenerator:
    """Generates random telemetry data"""
    
    def __init__(self):
        self.cycle_count = 0
    
    def generate_voltage(self) -> float:
        """Generate random voltage between 3.2 and 12.6"""
        return random.uniform(3.2, 12.6)
    
    def generate_temperature(self) -> float:
        """Generate random temperature between -50.0 and 80.0"""
        return random.uniform(-50.0, 80.0)
    
    def generate_sensor_active(self) -> bool:
        """Generate random sensor active status (80% true, 20% false)"""
        return random.random() < 0.8
    
    def increment_cycle(self) -> int:
        """Increment and return cycle count"""
        self.cycle_count += 1
        return self.cycle_count


class DatabaseWriter:
    """Handles database operations"""
    
    def __init__(self, config: Config):
        self.config = config
        self.conn = None
    
    def connect(self):
        """Establish database connection"""
        try:
            self.conn = psycopg2.connect(
                host=self.config.pghost,
                port=self.config.pgport,
                user=self.config.pguser,
                password=self.config.pgpassword,
                database=self.config.pgdatabase
            )
            logger.info(f"Connected to database {self.config.pgdatabase}")
        except Exception as e:
            logger.error(f"Database connection error: {e}")
            raise
    
    def insert_telemetry(self, recorded_at: str, voltage: float, temp: float, 
                        sensor_active: bool, cycle_count: int, source_file: str):
        """Insert telemetry record into database"""
        try:
            with self.conn.cursor() as cur:
                query = sql.SQL(
                    "INSERT INTO telemetry_legacy (recorded_at, voltage, temp, sensor_active, cycle_count, source_file) "
                    "VALUES (%s, %s, %s, %s, %s, %s)"
                )
                cur.execute(query, (recorded_at, voltage, temp, sensor_active, cycle_count, source_file))
                self.conn.commit()
                logger.info(f"Inserted telemetry record: {source_file}")
        except Exception as e:
            self.conn.rollback()
            logger.error(f"Database insert error: {e}")
            raise
    
    def close(self):
        """Close database connection"""
        if self.conn:
            self.conn.close()
            logger.info("Database connection closed")


class TelemetryService:
    """Main telemetry service"""
    
    def __init__(self, config: Config):
        self.config = config
        self.generator = TelemetryGenerator()
        self.db_writer = DatabaseWriter(config)
        
        # Ensure CSV directory exists
        Path(self.config.csv_out_dir).mkdir(parents=True, exist_ok=True)
        
        # Main CSV file path
        self.main_csv_file = os.path.join(self.config.csv_out_dir, 'telemetry_main.csv')
        self._ensure_csv_exists()
    
    def _ensure_csv_exists(self):
        """Create CSV file with headers if it doesn't exist"""
        if not os.path.exists(self.main_csv_file):
            with open(self.main_csv_file, 'w', newline='') as csvfile:
                writer = csv.writer(csvfile)
                writer.writerow(['recorded_at', 'voltage', 'temp', 'sensor_active', 'cycle_count', 'source_file'])
            logger.info(f"Created new CSV file: {self.main_csv_file}")
    
    def generate_and_store(self):
        """Generate telemetry data, write CSV, and insert to database"""
        try:
            # Generate timestamp and filename
            timestamp = datetime.now()
            timestamp_str = timestamp.strftime('%Y%m%d_%H%M%S')
            filename = f'telemetry_{timestamp_str}.csv'
            
            # Generate telemetry data
            recorded_at = timestamp.strftime('%Y-%m-%d %H:%M:%S')
            voltage = self.generator.generate_voltage()
            temp = self.generator.generate_temperature()
            sensor_active = self.generator.generate_sensor_active()
            cycle_count = self.generator.increment_cycle()
            
            # Append to main CSV file
            with open(self.main_csv_file, 'a', newline='') as csvfile:
                writer = csv.writer(csvfile)
                writer.writerow([
                    recorded_at, 
                    f'{voltage:.2f}', 
                    f'{temp:.2f}', 
                    'TRUE' if sensor_active else 'FALSE',
                    cycle_count,
                    filename
                ])
            
            logger.info(f"Appended data to CSV: cycle={cycle_count}, voltage={voltage:.2f}, temp={temp:.2f}")
            
            # Insert into database
            self.db_writer.insert_telemetry(recorded_at, voltage, temp, sensor_active, cycle_count, filename)
            
        except Exception as e:
            logger.error(f"Error in generate_and_store: {e}")
    
    def run(self):
        """Main service loop"""
        logger.info(f"Starting Telemetry Legacy Service (period: {self.config.gen_period_sec}s)")
        logger.info(f"CSV output directory: {self.config.csv_out_dir}")
        
        # Connect to database
        self.db_writer.connect()
        
        try:
            while True:
                self.generate_and_store()
                time.sleep(self.config.gen_period_sec)
        except KeyboardInterrupt:
            logger.info("Service interrupted by user")
        except Exception as e:
            logger.error(f"Service error: {e}")
        finally:
            self.db_writer.close()


def main():
    """Entry point"""
    config = Config()
    service = TelemetryService(config)
    service.run()


if __name__ == '__main__':
    main()
