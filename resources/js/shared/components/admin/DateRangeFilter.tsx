type Preset = { value: string; label: string };

type DateRangeFilterProps = {
  period: string;
  onPeriodChange: (value: string) => void;
  dateFrom: string;
  dateTo: string;
  onDateFromChange: (value: string) => void;
  onDateToChange: (value: string) => void;
  presets?: Preset[];
  /** Etiqueta del grupo. */
  label?: string;
  idPrefix?: string;
};

const DEFAULT_PRESETS: Preset[] = [
  { value: '7d', label: '7 días' },
  { value: '30d', label: '30 días' },
  { value: '90d', label: '90 días' },
];

/**
 * Filtro de rango de fechas reutilizable para reportes: presets rápidos
 * (7/30/90 días…) + opción "Personalizado" con campos desde/hasta.
 */
export function DateRangeFilter({
  period,
  onPeriodChange,
  dateFrom,
  dateTo,
  onDateFromChange,
  onDateToChange,
  presets = DEFAULT_PRESETS,
  label = 'Periodo',
  idPrefix = 'drf',
}: DateRangeFilterProps) {
  const isCustom = period === 'custom';

  return (
    <>
      <div className="filter-group">
        <label>{label}</label>
        <div className="period-toggle" role="group" aria-label={label}>
          {presets.map((preset) => (
            <button
              type="button"
              key={preset.value}
              className={`period-btn${period === preset.value ? ' active' : ''}`}
              onClick={() => onPeriodChange(preset.value)}
            >
              {preset.label}
            </button>
          ))}
          <button
            type="button"
            className={`period-btn${isCustom ? ' active' : ''}`}
            onClick={() => onPeriodChange('custom')}
          >
            Personalizado
          </button>
        </div>
      </div>

      {isCustom ? (
        <>
          <div className="filter-group">
            <label htmlFor={`${idPrefix}-from`}>Desde</label>
            <input
              type="date"
              id={`${idPrefix}-from`}
              value={dateFrom}
              max={dateTo || undefined}
              onChange={(e) => onDateFromChange(e.target.value)}
            />
          </div>
          <div className="filter-group">
            <label htmlFor={`${idPrefix}-to`}>Hasta</label>
            <input
              type="date"
              id={`${idPrefix}-to`}
              value={dateTo}
              min={dateFrom || undefined}
              onChange={(e) => onDateToChange(e.target.value)}
            />
          </div>
        </>
      ) : null}
    </>
  );
}
