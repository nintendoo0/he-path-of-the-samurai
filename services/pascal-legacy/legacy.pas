program LegacyCSV;

{$mode objfpc}{$H+}

uses
  SysUtils, DateUtils, Unix;

function GetEnvDef(const name, def: string): string;
var v: string;
begin
  v := GetEnvironmentVariable(name);
  if v = '' then Exit(def) else Exit(v);
end;

function RandFloat(minV, maxV: Double): Double;
begin
  Result := minV + Random * (maxV - minV);
end;

function RandInt(minV, maxV: Integer): Integer;
begin
  Result := minV + Random(maxV - minV + 1);
end;

function BoolToStr(val: Boolean): string;
begin
  if val then Exit('TRUE') else Exit('FALSE');
end;

function EscapeCSV(const s: string): string;
begin
  if Pos(',', s) > 0 then
    Exit('"' + StringReplace(s, '"', '""', [rfReplaceAll]) + '"')
  else
    Exit(s);
end;

procedure GenerateAndCopy();
var
  outDir, fn, fullpath: string;
  f: TextFile;
  ts: string;
  i: Integer;
  recordTime: TDateTime;
  voltage, temp: Double;
  sensor_active: Boolean;
  cycle_count: Integer;
  status_msg: string;
const
  StatusMessages: array[0..4] of string = (
    'Normal operation',
    'Low voltage warning',
    'Temperature spike detected',
    'Sensor calibration needed',
    'All systems nominal'
  );
begin
  outDir := GetEnvDef('CSV_OUT_DIR', '/data/csv');
  ts := FormatDateTime('yyyymmdd_hhnnss', Now);
  fn := 'telemetry_' + ts + '.csv';
  fullpath := IncludeTrailingPathDelimiter(outDir) + fn;

  // write CSV with proper types
  AssignFile(f, fullpath);
  Rewrite(f);
  // Header with typed columns
  Writeln(f, 'recorded_at,voltage,temp,sensor_active,cycle_count,status_msg,source_file');
  
  // Generate 10-15 records with varied data
  for i := 1 to RandInt(10, 15) do
  begin
    recordTime := IncSecond(Now, -RandInt(0, 3600)); // Last hour
    voltage := RandFloat(3.2, 12.6);
    temp := RandFloat(-50.0, 80.0);
    sensor_active := Random(10) > 2; // 80% TRUE
    cycle_count := RandInt(100, 10000);
    status_msg := StatusMessages[Random(5)];
    
    Writeln(f, 
      FormatDateTime('yyyy-mm-dd"T"hh:nn:ss', recordTime) + ',' +  // ISO8601 timestamp
      FormatFloat('0.00', voltage) + ',' +                          // Numeric
      FormatFloat('0.00', temp) + ',' +                             // Numeric
      BoolToStr(sensor_active) + ',' +                              // Boolean
      IntToStr(cycle_count) + ',' +                                 // Integer
      EscapeCSV(status_msg) + ',' +                                 // String
      EscapeCSV(fn)                                                 // String
    );
  end;
  CloseFile(f);
  
  WriteLn('[Pascal] CSV generated at: ', fullpath, ' with ', i, ' records');
end;

var period: Integer;
begin
  Randomize;
  period := StrToIntDef(GetEnvDef('GEN_PERIOD_SEC', '60'), 300);
  while True do
  begin
    try
      GenerateAndCopy();
    except
      on E: Exception do
        WriteLn('Legacy error: ', E.Message);
    end;
    Sleep(period * 1000);
  end;
end.
