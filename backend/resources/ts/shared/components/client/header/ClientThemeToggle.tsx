import { useEffect, useState } from 'react';

import { AnimatedThemeToggle } from '@/shared/components/ui/AnimatedThemeToggle';

type ClientThemeToggleProps = {
  onToggle: () => void;
};

export function ClientThemeToggle({ onToggle }: ClientThemeToggleProps) {
  const [isDark, setIsDark] = useState<boolean>(() => {
    if (typeof document === 'undefined') {
      return false;
    }
    return document.documentElement.dataset.theme === 'dark';
  });

  // Mantener sincronizado el estado si el tema cambia desde otro lugar.
  useEffect(() => {
    const target = document.documentElement;
    const observer = new MutationObserver(() => {
      setIsDark(target.dataset.theme === 'dark');
    });
    observer.observe(target, { attributes: true, attributeFilter: ['data-theme'] });
    return () => observer.disconnect();
  }, []);

  function handleToggle() {
    onToggle();
    setIsDark(document.documentElement.dataset.theme === 'dark');
  }

  return <AnimatedThemeToggle isDark={isDark} onToggle={handleToggle} className="cf4-anim-theme-toggle--client" />;
}
