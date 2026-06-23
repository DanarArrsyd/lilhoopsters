<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MembersImport extends Component
{
    use WithFileUploads;

    public $file = null;
    public bool $importing = false;
    public array $results  = [];
    public bool $done      = false;

    protected function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ];
    }

    public function import(): void
    {
        $this->validate();

        $this->importing = true;
        $this->results   = [];
        $this->done      = false;

        $path      = $this->file->getRealPath();
        $extension = strtolower($this->file->getClientOriginalExtension());

        try {
            if ($extension === 'csv') {
                $rows = $this->readCsv($path);
            } else {
                $rows = $this->readExcel($path);
            }
        } catch (\Throwable $e) {
            $this->results[] = ['status' => 'error', 'row' => 1, 'message' => 'Could not read file: ' . $e->getMessage()];
            $this->importing = false;
            $this->done      = true;
            return;
        }

        $parentRoleId = Role::where('name', 'parent')->value('id');
        $rowNum       = 2; // data starts at row 2

        foreach ($rows as $row) {
            // Skip completely empty rows
            $filled = array_filter(array_map('trim', $row));
            if (empty($filled)) {
                $rowNum++;
                continue;
            }

            [
                $parentName, $parentEmail, $parentWa, $childName, $childBirth, $childGender,
                $childSchool, $jerseyName, $jerseyNumber,
                $locName, $progName, $dayName, $pkgName, $remainingRaw, $expiryRaw, $startRaw,
            ] = array_pad(array_map('trim', $row), 16, '');

            // Validate required fields
            $errors = [];
            if (!$parentName)  $errors[] = 'Parent Name is required';
            if (!$parentEmail) $errors[] = 'Parent Email is required';
            elseif (!filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Parent Email is invalid';
            if (!$childName)   $errors[] = 'Child Name is required';
            if (!$childBirth)  $errors[] = 'Child Birth Date is required';
            if (!$childGender) $errors[] = 'Child Gender is required';
            elseif (!in_array(strtolower($childGender), ['male', 'female', 'laki-laki', 'perempuan'])) {
                $errors[] = 'Gender must be male or female';
            }

            // Parse birth date — accept DD/MM/YYYY or YYYY-MM-DD
            $birthDate = $this->parseBirthDate($childBirth);
            if ($childBirth && !$birthDate) {
                $errors[] = "Birth date '{$childBirth}' is not a valid date (use DD/MM/YYYY)";
            }

            // Resolve the optional active-enrollment block (J–P). Returns either
            // a ready-to-create enrollment payload, null (no enrollment), or
            // appends to $errors.
            $enrollment = $this->resolveEnrollment(
                $locName, $progName, $dayName, $pkgName, $remainingRaw, $expiryRaw, $startRaw, $errors
            );

            if ($errors) {
                $this->results[] = [
                    'status'  => 'error',
                    'row'     => $rowNum,
                    'name'    => $childName ?: '(empty)',
                    'message' => implode('; ', $errors),
                ];
                $rowNum++;
                continue;
            }

            // Normalize gender
            $gender = in_array(strtolower($childGender), ['male', 'laki-laki']) ? 'male' : 'female';

            // Normalize WhatsApp number (strip spaces, ensure 62 prefix)
            $wa = $parentWa ? $this->normalizePhone($parentWa) : null;

            // Whole row is atomic: parent + child + (optional) enrollment.
            $existingFlag = false;
            DB::transaction(function () use (
                $parentRoleId, $parentName, $parentEmail, $wa, $childName, $birthDate, $gender,
                $childSchool, $jerseyName, $jerseyNumber, $enrollment, &$existingFlag
            ) {
                $existing = User::where('email', strtolower($parentEmail))->first();
                if ($existing) {
                    $user = $existing;
                    if (!$user->whatsapp_number && $wa) {
                        $user->update(['whatsapp_number' => $wa]);
                    }
                } else {
                    $user = User::create([
                        'role_id'             => $parentRoleId,
                        'name'                => $parentName,
                        'email'               => strtolower($parentEmail),
                        'password'            => Hash::make(Str::random(16)),
                        'whatsapp_number'     => $wa,
                        'is_active'           => true,
                        'registration_status' => 'approved',
                    ]);
                }
                $existingFlag = (bool) $existing;

                $child = Child::create([
                    'user_id'        => $user->id,
                    'name'           => $childName,
                    'birth_date'     => $birthDate,
                    'gender'         => $gender,
                    'school'         => $childSchool ?: null,
                    'jersey_name'    => $jerseyName ?: null,
                    'jersey_number'  => $jerseyNumber ?: null,
                    'qr_identifier'  => (string) Str::uuid(),
                    'status'         => 'active',
                    'registered_at'  => now(),
                ]);

                if ($enrollment) {
                    Enrollment::create([
                        'child_id'           => $child->id,
                        'type'               => 'program',
                        'schedule_id'        => $enrollment['schedule_id'],
                        'package_id'         => $enrollment['package_id'],
                        'transaction_id'     => null,
                        'status'             => 'approved',
                        'approved_at'        => now(),
                        'started_at'         => $enrollment['started_at'],
                        'expires_at'         => $enrollment['expires_at'],
                        'remaining_sessions' => $enrollment['remaining_sessions'],
                        'total_sessions'     => $enrollment['total_sessions'],
                    ]);
                }
            });

            $label   = $existingFlag ? 'existing parent' : 'new parent';
            $suffix  = $enrollment ? ', enrolled' : '';
            $this->results[] = [
                'status'  => 'ok',
                'row'     => $rowNum,
                'name'    => $childName,
                'message' => "Imported — {$parentName} ({$label}){$suffix}",
            ];

            $rowNum++;
        }

        $this->importing = false;
        $this->done      = true;
        $this->file      = null;
    }

    private function readExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = [];

        foreach ($sheet->getRowIterator(2) as $row) {
            $cells = [];
            foreach ($row->getCellIterator('A', 'P') as $cell) {
                $cells[] = (string) $cell->getFormattedValue();
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    private function readCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            fgetcsv($handle); // skip header
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }

    private function parseBirthDate(string $value): ?string
    {
        $value = trim($value);
        // DD/MM/YYYY
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
            $date = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            return checkdate((int)$m[2], (int)$m[1], (int)$m[3]) ? $date : null;
        }
        // YYYY-MM-DD
        if (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $value, $m)) {
            return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $value : null;
        }
        // Excel numeric date (days since 1900-01-00)
        if (is_numeric($value)) {
            try {
                $ts = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float)$value);
                return date('Y-m-d', $ts);
            } catch (\Throwable) {}
        }
        return null;
    }

    private function normalizePhone(string $number): string
    {
        $number = preg_replace('/\D/', '', $number);
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }
        return $number;
    }

    /**
     * Resolve the optional active-enrollment columns (J–P).
     * Returns an enrollment payload, or null when no enrollment columns are
     * filled. On any problem it appends to $errors and returns null.
     */
    private function resolveEnrollment(
        string $locName, string $progName, string $dayName, string $pkgName,
        string $remainingRaw, string $expiryRaw, string $startRaw, array &$errors
    ): ?array {
        $any = $locName || $progName || $dayName || $pkgName
            || $remainingRaw !== '' || $expiryRaw || $startRaw;
        if (!$any) {
            return null; // plain member, no enrollment
        }

        $missing = [];
        if (!$locName)            $missing[] = 'Location';
        if (!$progName)           $missing[] = 'Program';
        if (!$dayName)            $missing[] = 'Day';
        if (!$pkgName)            $missing[] = 'Package';
        if ($remainingRaw === '') $missing[] = 'Remaining Sessions';
        if (!$expiryRaw)          $missing[] = 'Expiry Date';
        if ($missing) {
            $errors[] = 'Enrollment needs: ' . implode(', ', $missing);
            return null;
        }

        $day = $this->normalizeDay($dayName);
        if (!$day) {
            $errors[] = "Day '{$dayName}' is not a valid weekday";
            return null;
        }

        $location = Location::whereRaw('LOWER(name) = ?', [strtolower($locName)])->first();
        if (!$location) {
            $errors[] = "Location '{$locName}' not found";
            return null;
        }

        $program = Program::whereRaw('LOWER(name) = ?', [strtolower($progName)])->first();
        if (!$program) {
            $errors[] = "Program '{$progName}' not found";
            return null;
        }

        $package = Package::where('location_id', $location->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($pkgName)])->first();
        if (!$package) {
            $errors[] = "Package '{$pkgName}' not found in {$location->name}";
            return null;
        }

        $schedule = Schedule::where('location_id', $location->id)
            ->where('program_id', $program->id)
            ->where('day_of_week', $day)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->first();
        if (!$schedule) {
            $errors[] = "No active {$program->name} class on {$dayName} at {$location->name}";
            return null;
        }

        if (!is_numeric($remainingRaw) || (int) $remainingRaw < 0) {
            $errors[] = "Remaining Sessions '{$remainingRaw}' must be a number";
            return null;
        }

        $expiry = $this->parseBirthDate($expiryRaw);
        if (!$expiry) {
            $errors[] = "Expiry Date '{$expiryRaw}' is not a valid date (use DD/MM/YYYY)";
            return null;
        }

        $start = null;
        if ($startRaw) {
            $start = $this->parseBirthDate($startRaw);
            if (!$start) {
                $errors[] = "Start Date '{$startRaw}' is not a valid date (use DD/MM/YYYY)";
                return null;
            }
        }

        return [
            'schedule_id'        => $schedule->id,
            'package_id'         => $package->id,
            'remaining_sessions' => (int) $remainingRaw,
            'total_sessions'     => $package->session_count ?? (int) $remainingRaw,
            'expires_at'         => $expiry,
            'started_at'         => $start,
        ];
    }

    private function normalizeDay(string $day): ?string
    {
        return match (strtolower(trim($day))) {
            'monday', 'senin'                 => 'monday',
            'tuesday', 'selasa'               => 'tuesday',
            'wednesday', 'rabu'               => 'wednesday',
            'thursday', 'kamis'               => 'thursday',
            'friday', 'jumat', "jum'at"       => 'friday',
            'saturday', 'sabtu'               => 'saturday',
            'sunday', 'minggu', 'ahad'        => 'sunday',
            default                           => null,
        };
    }

    public function clearResults(): void
    {
        $this->file      = null;
        $this->results   = [];
        $this->done      = false;
        $this->importing = false;
    }

    public function render()
    {
        $okCount    = collect($this->results)->where('status', 'ok')->count();
        $errorCount = collect($this->results)->where('status', 'error')->count();

        return view('livewire.admin.members-import', compact('okCount', 'errorCount'));
    }
}
