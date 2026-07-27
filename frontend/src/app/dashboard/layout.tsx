"use client";

import type { ReactNode } from "react";

import { ProtectedRoute } from "@/components/auth/ProtectedRoute";
import { DashboardShell } from "@/components/dashboard/DashboardShell";

interface DashboardLayoutProps {
  children: ReactNode;
}

export default function DashboardLayout({
  children,
}: DashboardLayoutProps) {
  return (
    <ProtectedRoute>
      <DashboardShell>
        {children}
      </DashboardShell>
    </ProtectedRoute>
  );
}