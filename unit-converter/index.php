<?php
/**
 * Unit Converter
 * - Single-file PHP app that converts an input value from one unit to all units in the selected category.
 * - Categories: distance, mass, volume, temperature, area, speed, time, pressure, energy, power, data, angle
 * - Conversion approach: normalize to a base unit per category, then convert to targets.
 */

// Unit definition: ['code' => [ 'name' => '', 'factor' => float ]] where factor converts unit -> base unit.
// For temperature, special handling is used.

function units_definitions(): array {
    return [
        'distance' => [
            'base' => 'm',
            'units' => [
                'mm' => ['name'=>'Millimeter','factor'=>0.001],
                'cm' => ['name'=>'Centimeter','factor'=>0.01],
                'm'  => ['name'=>'Meter','factor'=>1.0],
                'km' => ['name'=>'Kilometer','factor'=>1000.0],
                'in' => ['name'=>'Inch','factor'=>0.0254],
                'ft' => ['name'=>'Foot','factor'=>0.3048],
                'yd' => ['name'=>'Yard','factor'=>0.9144],
                'mi' => ['name'=>'Mile','factor'=>1609.344],
            ],
        ],
        'mass' => [
            'base' => 'kg',
            'units' => [
                'mg'=>['name'=>'Milligram','factor'=>1e-6],
                'g'=>['name'=>'Gram','factor'=>0.001],
                'kg'=>['name'=>'Kilogram','factor'=>1.0],
                't'=>['name'=>'Metric ton','factor'=>1000.0],
                'oz'=>['name'=>'Ounce','factor'=>0.028349523125],
                'lb'=>['name'=>'Pound','factor'=>0.45359237],
            ],
        ],
        'volume' => [
            'base' => 'l',
            'units' => [
                'ml'=>['name'=>'Milliliter','factor'=>0.001],
                'l'=>['name'=>'Liter','factor'=>1.0],
                'm3'=>['name'=>'Cubic meter','factor'=>1000.0],
                'tsp'=>['name'=>'Teaspoon (US)','factor'=>0.00492892159375],
                'tbsp'=>['name'=>'Tablespoon (US)','factor'=>0.01478676478125],
                'cup'=>['name'=>'Cup (US)','factor'=>0.2365882365],
                'floz'=>['name'=>'Fluid ounce (US)','factor'=>0.0295735295625],
                'pt'=>['name'=>'Pint (US)','factor'=>0.473176473],
                'qt'=>['name'=>'Quart (US)','factor'=>0.946352946],
                'gal'=>['name'=>'Gallon (US)','factor'=>3.785411784],
            ],
        ],
        'temperature' => [
            'base' => 'K',
            'units' => [
                'C'=>['name'=>'Celsius'],
                'F'=>['name'=>'Fahrenheit'],
                'K'=>['name'=>'Kelvin'],
                'R'=>['name'=>'Rankine'],
            ],
        ],
        'area' => [
            'base' => 'm2',
            'units' => [
                'mm2'=>['name'=>'Square millimeter','factor'=>1e-6],
                'cm2'=>['name'=>'Square centimeter','factor'=>1e-4],
                'm2'=>['name'=>'Square meter','factor'=>1.0],
                'km2'=>['name'=>'Square kilometer','factor'=>1e6],
                'in2'=>['name'=>'Square inch','factor'=>0.00064516],
                'ft2'=>['name'=>'Square foot','factor'=>0.09290304],
                'acre'=>['name'=>'Acre','factor'=>4046.8564224],
                'ha'=>['name'=>'Hectare','factor'=>10000.0],
            ],
        ],
        'speed' => [
            'base' => 'm/s',
            'units' => [
                'm/s'=>['name'=>'Meters per second','factor'=>1.0],
                'km/h'=>['name'=>'Kilometers per hour','factor'=>(1000.0/3600.0)],
                'mph'=>['name'=>'Miles per hour','factor'=>1609.344/3600.0],
                'knot'=>['name'=>'Knot','factor'=>1852.0/3600.0],
                'ft/s'=>['name'=>'Feet per second','factor'=>0.3048],
            ],
        ],
        'time' => [
            'base' => 's',
            'units' => [
                'ms'=>['name'=>'Millisecond','factor'=>0.001],
                's'=>['name'=>'Second','factor'=>1.0],
                'min'=>['name'=>'Minute','factor'=>60.0],
                'h'=>['name'=>'Hour','factor'=>3600.0],
                'day'=>['name'=>'Day','factor'=>86400.0],
            ],
        ],
        'pressure' => [
            'base' => 'Pa',
            'units' => [
                'Pa'=>['name'=>'Pascal','factor'=>1.0],
                'kPa'=>['name'=>'Kilopascal','factor'=>1000.0],
                'bar'=>['name'=>'Bar','factor'=>100000.0],
                'atm'=>['name'=>'Atmosphere','factor'=>101325.0],
                'psi'=>['name'=>'Pound per square inch','factor'=>6894.757293168],
            ],
        ],
        'energy' => [
            'base' => 'J',
            'units' => [
                'J'=>['name'=>'Joule','factor'=>1.0],
                'kJ'=>['name'=>'Kilojoule','factor'=>1000.0],
                'cal'=>['name'=>'Calorie','factor'=>4.184],
                'kcal'=>['name'=>'Kilocalorie','factor'=>4184.0],
                'Wh'=>['name'=>'Watt hour','factor'=>3600.0],
                'kWh'=>['name'=>'Kilowatt hour','factor'=>3.6e6],
            ],
        ],
        'power' => [
            'base' => 'W',
            'units' => [
                'W'=>['name'=>'Watt','factor'=>1.0],
                'kW'=>['name'=>'Kilowatt','factor'=>1000.0],
                'MW'=>['name'=>'Megawatt','factor'=>1e6],
                'hp'=>['name'=>'Horsepower','factor'=>745.69987158227022],
            ],
        ],
        'data' => [
            'base' => 'B',
            'units' => [
                'B'=>['name'=>'Byte','factor'=>1.0],
                'KB'=>['name'=>'Kilobyte (1000)','factor'=>1e3],
                'KiB'=>['name'=>'Kibibyte (1024)','factor'=>1024],
                'MB'=>['name'=>'Megabyte (1000)','factor'=>1e6],
                'MiB'=>['name'=>'Mebibyte (1024^2)','factor'=>1024*1024],
                'GB'=>['name'=>'Gigabyte (1000)','factor'=>1e9],
                'GiB'=>['name'=>'Gibibyte (1024^3)','factor'=>1024*1024*1024],
                'TB'=>['name'=>'Terabyte (1000)','factor'=>1e12],
            ],
        ],
        'angle' => [
            'base' => 'rad',
            'units' => [
                'deg'=>['name'=>'Degree','factor'=>M_PI/180.0],
                'rad'=>['name'=>'Radian','factor'=>1.0],
                'grad'=>['name'=>'Grad','factor'=>M_PI/200.0],
            ],
        ],
    ];
}

// Temperature conversion utilities
function temp_to_kelvin(float $value, string $from): float {
    switch ($from) {
        case 'C': return $value + 273.15;
        case 'F': return ($value + 459.67) * 5/9;
        case 'K': return $value;
        case 'R': return $value * 5/9;
        default: return $value; // fallback
    }
}

function kelvin_to_temp(float $k, string $to): float {
    switch ($to) {
        case 'C': return $k - 273.15;
        case 'F': return $k * 9/5 - 459.67;
        case 'K': return $k;
        case 'R': return $k * 9/5;
        default: return $k;
    }
}

function convert_value(float $value, string $from, string $to, string $category, array $defs) {
    if ($category === 'temperature') {
        $k = temp_to_kelvin($value, $from);
        return kelvin_to_temp($k, $to);
    }
    $units = $defs[$category]['units'];
    if (!isset($units[$from]) || !isset($units[$to])) return null;
    $base = $defs[$category]['base'];
    $toBase = $units[$from]['factor'];
    $fromBase = $units[$to]['factor'];
    // value_in_base = value * toBase
    $valueBase = $value * $toBase;
    // result = valueBase / fromBase
    return $valueBase / $fromBase;
}

// Handle form
$defs = units_definitions();
$categories = array_keys($defs);
$category = $_POST['category'] ?? ($_GET['category'] ?? 'distance');
if (!isset($defs[$category])) $category = 'distance';
$fromUnit = $_POST['from'] ?? ($_GET['from'] ?? array_key_first($defs[$category]['units']));
$value = isset($_POST['value']) ? (float)$_POST['value'] : (isset($_GET['value']) ? (float)$_GET['value'] : 1.0);
$precision = isset($_POST['precision']) ? intval($_POST['precision']) : 6;

$results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert'])) {
    $units = $defs[$category]['units'];
    foreach ($units as $code => $meta) {
        if ($category === 'temperature') {
            $res = convert_value($value, $fromUnit, $code, $category, $defs);
        } else {
            $res = convert_value($value, $fromUnit, $code, $category, $defs);
        }
        $results[$code] = $res;
    }
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Unit Converter</title>
  <style>
    body{font-family:Segoe UI,Arial,Helvetica,sans-serif;background:#f6f7fb;color:#111;padding:18px}
    .wrap{max-width:1100px;margin:0 auto}
    .card{background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 18px rgba(2,6,23,.06);margin-bottom:16px}
    label{display:block;font-weight:600;margin-bottom:6px}
    input,select{padding:8px;border-radius:6px;border:1px solid #d1d5db}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
    th{background:#f3f4f6}
    .muted{color:#6b7280}
    .controls{display:flex;gap:12px;flex-wrap:wrap;align-items:end}
    .small{width:160px}
    button{background:#0b5fff;color:#fff;border:0;padding:8px 12px;border-radius:6px;cursor:pointer}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>Unit Converter</h1>
      <div class="muted">Select a category, enter a value and a source unit. The table shows conversions to all units in the category.</div>
    </div>

    <div class="card">
      <form method="post">
        <div class="controls">
          <div>
            <label for="category">Category</label>
            <select id="category" name="category" onchange="this.form.submit()">
              <?php foreach($categories as $c): ?>
                <option value="<?php echo h($c);?>" <?php if($c===$category) echo 'selected';?>><?php echo h(ucfirst($c));?></option>
              <?php endforeach;?>
            </select>
          </div>

          <div>
            <label for="value">Value</label>
            <input id="value" name="value" type="number" step="any" value="<?php echo h($value);?>">
          </div>

          <div>
            <label for="from">From unit</label>
            <select id="from" name="from">
              <?php foreach($defs[$category]['units'] as $code=>$meta): ?>
                <option value="<?php echo h($code); ?>" <?php if($code===$fromUnit) echo 'selected'; ?>><?php echo h($meta['name'] . " (".$code.")");?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="small">
            <label for="precision">Precision</label>
            <input id="precision" name="precision" type="number" value="<?php echo h($precision);?>" min="0" max="12">
          </div>

          <div style="align-self:flex-end">
            <button name="convert" type="submit">Convert</button>
          </div>
        </div>
      </form>

      <?php if ($results): ?>
        <h3 style="margin-top:16px">Results (<?php echo h($value); ?> <?php echo h($fromUnit); ?>)</h3>
        <table>
          <thead><tr><th>Unit</th><th>Value</th><th>Description</th></tr></thead>
          <tbody>
            <?php foreach($defs[$category]['units'] as $code => $meta):
              $val = $results[$code];
              $display = is_numeric($val) ? number_format($val, max(0,min(12,$precision))) : '—';
            ?>
            <tr>
              <td><?php echo h($code);?></td>
              <td><code><?php echo h($display);?></code></td>
              <td><?php echo h($meta['name'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>Supported categories & units</h3>
      <p class="muted">Full list available in project README file for this tool.</p>
    </div>
  </div>
</body>
</html>
