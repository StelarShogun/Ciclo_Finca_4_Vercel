export function profileInitials(name: string, firstSurname: string) {
  return `${name.charAt(0)}${firstSurname.charAt(0)}`.toUpperCase();
}

export function fullName(parts: { name: string; first_surname: string; second_surname: string }) {
  return [parts.name, parts.first_surname, parts.second_surname].filter(Boolean).join(' ');
}

export function passwordStrengthLevel(value: string) {
  if (!value) {
    return null;
  }

  let score = 0;
  if (value.length >= 8) score += 1;
  if (/[A-Z]/.test(value)) score += 1;
  if (/[0-9]/.test(value)) score += 1;
  if (/[^A-Za-z0-9]/.test(value)) score += 1;

  const levels = [
    { width: '25%', color: '#d32f2f', label: 'Débil' },
    { width: '50%', color: '#f57c00', label: 'Regular' },
    { width: '75%', color: '#fbc02d', label: 'Buena' },
    { width: '100%', color: '#235347', label: 'Fuerte' },
  ];

  return levels[Math.max(score - 1, 0)];
}
