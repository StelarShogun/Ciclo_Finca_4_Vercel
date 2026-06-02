import type { PropsWithChildren } from 'react';

export function AuthLayout({ children }: PropsWithChildren) {
  return (
    <main className="auth-page">
      <div className="auth-card">{children}</div>
    </main>
  );
}
