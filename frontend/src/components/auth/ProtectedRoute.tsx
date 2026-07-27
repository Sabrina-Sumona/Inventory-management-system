"use client";

import {
  useEffect,
  type ReactNode,
} from "react";
import { useRouter } from "next/navigation";

import { useAuth } from "@/contexts/AuthContext";

interface ProtectedRouteProps {
  children: ReactNode;
}

export function ProtectedRoute({
  children,
}: ProtectedRouteProps) {
  const router = useRouter();
  const { user, isLoading } = useAuth();

  useEffect(() => {
    if (!isLoading && !user) {
      router.replace("/login");
    }
  }, [isLoading, user, router]);

  if (isLoading) {
    return (
      <main className="flex min-h-screen items-center justify-center bg-slate-100">
        <div className="text-center">
          <div className="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-slate-300 border-t-emerald-600" />

          <p className="mt-4 text-sm text-slate-600">
            Checking authentication...
          </p>
        </div>
      </main>
    );
  }

  if (!user) {
    return null;
  }

  return children;
}