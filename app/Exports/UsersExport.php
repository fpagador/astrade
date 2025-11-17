<?php
namespace App\Exports;

use App\Enums\ContractType;
use App\Enums\NotificationType;
use App\Enums\RoleEnum;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Enums\UserTypeEnum;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Carbon;

/**
 * UsersExport handles exporting users to Excel with dynamic columns
 * depending on the type (management or mobile) and includes basic styling.
 */
class UsersExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
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
                    'Foto' => $user->photo ? 'Sí' : 'No',
                    'Empresa' => $user->company?->name ?? '—',
                    'Calendario laboral' => $user->workCalendarTemplate?->name ?? '—',
                    'Horario de trabajo' => $user->work_schedule ?? '—',
                    'Tipo de contrato' => ContractType::label(ContractType::from($user->contract_type)) ?? '—',
                    'Fecha de inicio de contrato' => $user->contract_start_date
                        ? Carbon::parse($user->contract_start_date)->format('d/m/Y')
                        : '—',
                    'Tipo de notificación' => NotificationType::label(NotificationType::from($user->notification_type)) ?? '—',
                ];
            }

            // Management users
            return [
                'Nombre' => $user->name,
                'Apellidos' => $user->surname,
                'DNI' => $user->dni,
                'Email' => $user->email,
                'Teléfono' => $user->phone,
                'Foto' => $user->photo ? 'Sí' : 'No',
                'Rol' => RoleEnum::from($user->role?->role_name)->label() ?? '',
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
            return [
                'Nombre',
                'Apellidos',
                'DNI',
                'Email',
                'Teléfono',
                'Foto',
                'Empresa',
                'Calendario laboral',
                'Horario de trabajo',
                'Tipo de contrato',
                'Fecha de inicio de contrato',
                'Tipo de notificación'
            ];
        }

        return ['Nombre', 'Apellidos', 'DNI', 'Email', 'Teléfono', 'Foto', 'Rol'];
    }

    /**
     * Apply basic styling to the Excel sheet.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'A06CD5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
            ],

        ]);

        return [];
    }
}
