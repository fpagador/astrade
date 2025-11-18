<?php
namespace App\Exports;

use App\Enums\ContractType;
use App\Enums\NotificationType;
use App\Enums\RoleEnum;
use App\Enums\UserTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

/**
 * UsersExport handles exporting users to Excel with dynamic columns
 * depending on the type (management or mobile) and includes basic styling.
 */
class UsersExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
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

    public function query()
    {
        return $this->query;
    }

    /**
     * Mapea cada registro antes de exportarlo
     *
     * @param $user
     */
    public function map($user): array
    {
        if ($this->type === UserTypeEnum::MOBILE->value) {

            return [
                $user->name,
                $user->surname,
                $user->dni,
                $user->email,
                $user->phone,
                $user->photo ? 'Sí' : 'No',
                $user->company?->name ?? '—',
                $user->workCalendarTemplate?->name ?? '—',
                $user->work_schedule ?? '—',
                ContractType::label(ContractType::from($user->contract_type)) ?? '—',
                $user->contract_start_date
                    ? Carbon::parse($user->contract_start_date)->format('d/m/Y')
                    : '—',
                NotificationType::label(NotificationType::from($user->notification_type)) ?? '—',
            ];
        }

        return [
            $user->name,
            $user->surname,
            $user->dni,
            $user->email,
            $user->phone,
            $user->photo ? 'Sí' : 'No',
            RoleEnum::from($user->role?->role_name)->label() ?? '',
        ];
    }

    /**
     * Columnas
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
                'Tipo de notificación',
            ];
        }

        return [
            'Nombre',
            'Apellidos',
            'DNI',
            'Email',
            'Teléfono',
            'Foto',
            'Rol',
        ];
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
        ]);

        return [];
    }
}
