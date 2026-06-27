export async function parseJsonResponse<T>(response: Response): Promise<
  | { ok: true; data: T }
  | { ok: false; message: string }
> {
  const contentType = response.headers.get('content-type') ?? '';

  if (!contentType.includes('application/json')) {
    return {
      ok: false,
      message:
        response.status === 419
          ? 'La sesión expiró. Recarga la página e inténtalo de nuevo.'
          : 'Respuesta inesperada del servidor.',
    };
  }

  const data = (await response.json()) as T;

  return { ok: true, data };
}
