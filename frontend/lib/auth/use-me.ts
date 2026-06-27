"use client";

import { useQuery } from "@tanstack/react-query";
import { me, type Me } from "@/lib/api/auth";

/** Sesión actual (admin o cliente). retry:false para que un 401 no reintente. */
export function useMe() {
  return useQuery<Me>({
    queryKey: ["me"],
    queryFn: me,
    retry: false,
    staleTime: 60_000,
  });
}
