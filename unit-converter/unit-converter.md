# Unit Converter

This tool is a general-purpose unit converter implemented as a single PHP page: `unit-converter/index.php`.

It accepts a numeric input value, a unit category (distance, mass, volume, temperature, area, speed, time, pressure, energy, power, data, angle), and a source unit. When you submit the form the tool converts the value into every supported unit in the chosen category and displays a tidy results table.

## Goals / design
- Simple, self-contained PHP UI that works without JavaScript (JS enhances UX but is not required).
- Each category has a stable base unit. Conversions go: input unit -> base -> output unit. Temperature uses explicit formulae because it's not a pure multiplier.
- Output precision is configurable.

## Location
- Tool code: `unit-converter/index.php`
- Documentation: `unit-converter/unit-converter.md`

## How to use (UI)
1. Open `unit-converter/index.php` in your web server (for example, http://localhost/proishs-webtools/unit-converter/).
2. Select a category from the `Category` drop-down.
3. Enter a numeric value and pick the `From unit` for that value.
4. (Optional) Change `Precision` — number of decimal places in the table output.
5. Click `Convert`.

The page will display a table converting your input to all supported units in the chosen category.

## Programmatic API
The page uses POST form parameters. You can also call it with GET for convenience.

Parameters:
- `category` — category key (distance, mass, volume, temperature, area, speed, time, pressure, energy, power, data, angle).
- `value` — numeric value to convert.
- `from` — unit code (see the supported units table below).
- `precision` — integer number of decimal places (0–12).

Example (GET):

`/unit-converter/index.php?category=distance&value=1&from=km&precision=4`

This will show conversion of 1 kilometer into every supported distance unit.

## Supported categories and units
The tool attempts to cover common SI and imperial units. Each entry shows the unit code used in the UI.

- distance (base: meter `m`)
  - `mm` Millimeter
  - `cm` Centimeter
  - `m` Meter
  - `km` Kilometer
  - `in` Inch
  - `ft` Foot
  - `yd` Yard
  - `mi` Mile

- mass (base: kilogram `kg`)
  - `mg` Milligram
  - `g` Gram
  - `kg` Kilogram
  - `t` Metric ton
  - `oz` Ounce
  - `lb` Pound

- volume (base: liter `l`)
  - `ml` Milliliter
  - `l` Liter
  - `m3` Cubic meter
  - `tsp` Teaspoon (US)
  - `tbsp` Tablespoon (US)
  - `cup` Cup (US)
  - `floz` Fluid ounce (US)
  - `pt` Pint (US)
  - `qt` Quart (US)
  - `gal` Gallon (US)

- temperature (base: Kelvin `K`) — special handling
  - `C` Celsius
  - `F` Fahrenheit
  - `K` Kelvin
  - `R` Rankine

- area (base: square meter `m2`)
  - `mm2` Square millimeter
  - `cm2` Square centimeter
  - `m2` Square meter
  - `km2` Square kilometer
  - `in2` Square inch
  - `ft2` Square foot
  - `acre` Acre
  - `ha` Hectare

- speed (base: m/s)
  - `m/s` Meters per second
  - `km/h` Kilometers per hour
  - `mph` Miles per hour
  - `knot` Knot
  - `ft/s` Feet per second

- time (base: second `s`)
  - `ms` Millisecond
  - `s` Second
  - `min` Minute
  - `h` Hour
  - `day` Day

- pressure (base: Pascal `Pa`)
  - `Pa` Pascal
  - `kPa` Kilopascal
  - `bar` Bar
  - `atm` Atmosphere
  - `psi` Pound per square inch

- energy (base: Joule `J`)
  - `J` Joule
  - `kJ` Kilojoule
  - `cal` Calorie
  - `kcal` Kilocalorie
  - `Wh` Watt hour
  - `kWh` Kilowatt hour

- power (base: Watt `W`)
  - `W` Watt
  - `kW` Kilowatt
  - `MW` Megawatt
  - `hp` Horsepower

- data (base: Byte `B`)
  - `B` Byte
  - `KB` Kilobyte (1000)
  - `KiB` Kibibyte (1024)
  - `MB` Megabyte (1000^2)
  - `MiB` Mebibyte (1024^2)
  - `GB` Gigabyte (1000^3)
  - `GiB` Gibibyte (1024^3)
  - `TB` Terabyte

- angle (base: radian `rad`)
  - `deg` Degree
  - `rad` Radian
  - `grad` Grad

## Implementation notes
- The conversion table for non-temperature categories uses multiplicative factors relative to a base unit. Example: inches -> meters uses factor 0.0254.
- Temperature conversions use helper functions to convert to Kelvin and back because they require offsets and scaling.
- Precision is clamped to a reasonable range (0–12) in the UI.

## Extending the tool
To add a category or a new unit:
1. Open `unit-converter/index.php`.
2. Update the `units_definitions()` array: add a category key, define `base` and `units` with `code=>['name'=>..., 'factor'=>...]`. For temperature-like categories add special handlers.
3. Save and reload the page.

Notes on adding temperature-like categories: any unit that requires more than a linear multiplier needs its own pair of conversion functions (to/from base). Follow the `temp_to_kelvin()` and `kelvin_to_temp()` pattern.

## Examples
- Convert 5 kilometers to all distance units: choose category `distance`, value `5`, from `km`.
- Convert 32 Fahrenheit to Celsius & Kelvin: category `temperature`, value `32`, from `F`.

## Tests
Minimal test checklist:
- UI test: select each category and convert a sample value (1 or 100) to ensure the page returns a table with no errors.
- Sanity checks for well-known conversions:
  - distance: 1 km -> 1000 m
  - mass: 1 lb -> 0.45359237 kg
  - temperature: 0 C -> 273.15 K and 32 F -> 0 C
  - data: 1 KiB -> 1024 B
- Edge cases:
  - Very large numbers (1e12) and very small numbers (1e-12) — check precision formatting.
  - Invalid category or unit — the UI falls back to a safe default (distance).

## Caveats and limitations
- The tool is intentionally simple and synchronous. For very large data sets or many batched conversions, consider implementing an API endpoint that streams results or a background job.
- Unit lists are not exhaustive. Add or tweak factors in `units_definitions()` as needed.

## Next improvements (suggested)
- Add copy-to-clipboard buttons beside each result.
- Support selecting a subset of target units to show.
- Add CSV/JSON export of results.
- Add automated unit tests (PHPUnit) to verify conversion factors.

## Contact / contribution
Contributions are welcome — open a pull request with unit additions or fixes. When adding units, include a brief source for conversion factors (official SI tables or authoritative references).
