import { isAxiosError } from "axios";

export function apiErrorMessage(error: unknown, fallback: string): string {
  return (isAxiosError(error) && (error.response?.data?.message as string | undefined)) || fallback;
}
