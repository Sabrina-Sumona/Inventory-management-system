"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

import { useAuth } from "@/contexts/AuthContext";

export default function DashboardPage() {
  const router = useRouter();
  const { user, logout } = useAuth();

  const [isLoggingOut, setIsLoggingOut] =
    useState(false);

  async function handleLogout(): Promise<void> {
    setIsLoggingOut(true);

    try {
      await logout();

      router.replace("/login");
      router.refresh();
    } finally {
      setIsLoggingOut(false);
    }
  }

  return (
    <main className="min-h-screen bg-slate-100 p-6">
      <div className="mx-auto max-w-7xl">
        <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
          <div className="flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
            <div>
              <p className="text-sm font-medium text-emerald-700">
                Authentication successful
              </p>

              <h1 className="mt-2 text-3xl font-bold text-slate-950">
                Welcome, {user?.name}
              </h1>

              <p className="mt-2 text-slate-600">
                {user?.email}
              </p>
            </div>

            <button
              type="button"
              onClick={handleLogout}
              disabled={isLoggingOut}
              className="rounded-lg bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-500"
            >
              {isLoggingOut
                ? "Signing out..."
                : "Sign out"}
            </button>
          </div>
        </div>
      </div>
    </main>
  );
}