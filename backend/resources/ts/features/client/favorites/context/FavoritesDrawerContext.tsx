import type { PropsWithChildren } from 'react';
import { createContext, useCallback, useContext, useMemo, useState } from 'react';

type FavoritesDrawerContextValue = {
  isOpen: boolean;
  open: () => void;
  close: () => void;
  refreshToken: number;
  requestRefresh: () => void;
};

const FavoritesDrawerContext = createContext<FavoritesDrawerContextValue | null>(null);

export function FavoritesDrawerProvider({ children }: PropsWithChildren) {
  const [isOpen, setIsOpen] = useState(false);
  const [refreshToken, setRefreshToken] = useState(0);

  const open = useCallback(() => setIsOpen(true), []);
  const close = useCallback(() => setIsOpen(false), []);
  const requestRefresh = useCallback(() => setRefreshToken((value) => value + 1), []);

  const value = useMemo(
    () => ({ isOpen, open, close, refreshToken, requestRefresh }),
    [close, isOpen, open, refreshToken, requestRefresh],
  );

  return <FavoritesDrawerContext.Provider value={value}>{children}</FavoritesDrawerContext.Provider>;
}

export function useFavoritesDrawer() {
  const context = useContext(FavoritesDrawerContext);
  if (!context) {
    throw new Error('useFavoritesDrawer must be used within FavoritesDrawerProvider');
  }

  return context;
}
