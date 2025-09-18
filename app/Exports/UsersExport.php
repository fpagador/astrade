<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Enums\UserTypeEnum;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * UsersExport handles exporting users to Excel with dynamic columns
 * depending on the type (management or mobile) and includes basic styling.
 */
class UsersExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $query;
    protected $type;

    /**
     * UsersExport constructor.
     *
     * @param Builder $query
     * @param string $type Either 'management' or 'mobile'
     */
    public function __construct(Builder $query, string $type)
    {
        $this->query = $query;
        $this->type = $type;
    }

    /**
     * Get the collection of users to export.
     *
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->query->get()->map(function ($user) {
            if ($this->type === UserTypeEnum::MOBILE->value) {
                return [
                    'Nombre' => $user->name,
                    'Apellidos' => $user->surname,
                    'DNI' => $user->dni,
                    'Email' => $user->email,
                    'Teléfono' => $user->phone,
                    'Empresa' => $user->company?->name ?? '—',
                ];
            }

            // Management users
            return [
                'Nombre' => $user->name,
                'Apellidos' => $user->surname,
                'DNI' => $user->dni,
                'Email' => $user->email,
                'Teléfono' => $user->phone,
                'Rol' => $user->role?->role_name ?? '',
                'Puede recibir llamada' => $user->can_be_called ? 'Sí' : 'No',
            ];
        });
    }

    /**
     * Headings for the Excel sheet.
     *
     * @return array
     */
    public function headings(): array
    {
        if ($this->type === UserTypeEnum::MOBILE->value) {
            return ['Nombre', 'Apellidos', 'DNI', 'Email', 'Teléfono', 'Empresa'];
        }

        return ['Nombre', 'Apellidos', 'DNI', 'Email', 'Teléfono', 'Rol', 'Puede recibir llamada'];
    }

    /**
     * Apply basic styling to the Excel sheet.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Bold and background color for the header row
        $sheet->getStyle('A1:Z1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4F46E5'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];
    }
}
