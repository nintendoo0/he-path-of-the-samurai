FROM python:3.12-slim

# Install PostgreSQL client
RUN apt-get update && apt-get install -y \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Install Python dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy service
COPY telemetry_service.py .
RUN chmod +x telemetry_service.py

CMD ["python", "-u", "telemetry_service.py"]
