import { useEffect, useState } from 'react';

const HEARTBEAT_URL = '/notifications/heartbeat';
const POLL_INTERVAL_MS = 45_000;

type HeartbeatResponse = {
  unread_count?: number;
};

/**
 * Conteo de notificaciones no leídas del cliente, refrescado por polling ligero
 * contra el heartbeat existente. Pausa mientras la pestaña está oculta.
 */
export function useNotificationCount(enabled: boolean): number {
  const [count, setCount] = useState(0);

  useEffect(() => {
    if (!enabled) {
      setCount(0);
      return;
    }

    let cancelled = false;
    let timer: ReturnType<typeof setTimeout> | null = null;

    async function poll() {
      try {
        const response = await fetch(HEARTBEAT_URL, {
          headers: { Accept: 'application/json' },
          credentials: 'same-origin',
        });
        if (response.ok) {
          const data = (await response.json()) as HeartbeatResponse;
          if (!cancelled && typeof data.unread_count === 'number') {
            setCount(data.unread_count);
          }
        }
      } catch {
        // silencioso: el badge simplemente no se actualiza este ciclo
      } finally {
        if (!cancelled) {
          timer = setTimeout(poll, POLL_INTERVAL_MS);
        }
      }
    }

    function onVisibilityChange() {
      if (document.visibilityState === 'visible' && !cancelled) {
        if (timer) {
          clearTimeout(timer);
        }
        poll();
      }
    }

    poll();
    document.addEventListener('visibilitychange', onVisibilityChange);

    return () => {
      cancelled = true;
      if (timer) {
        clearTimeout(timer);
      }
      document.removeEventListener('visibilitychange', onVisibilityChange);
    };
  }, [enabled]);

  return count;
}
