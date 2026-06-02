export type InertiaErrors = Record<string, string | string[] | undefined>;

export function firstError(errors: InertiaErrors, key: string): string | undefined {
  const value = errors[key];
  if (!value) return undefined;
  if (Array.isArray(value)) return value[0];
  return value;
}

