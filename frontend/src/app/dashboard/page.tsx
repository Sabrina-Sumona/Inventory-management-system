"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

import { useAuth } from "@/contexts/AuthContext";

interface SummaryCardProps {
  label: string;
  value: string;
  description?: string;
}

function SummaryCard({
  label,
  value,
  description,
}: SummaryCardProps) {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <p className="text-sm font-medium text-slate-500">
        {label}
      </p>

      <p className="mt-2 text-xl font-bold text-slate-950">
        {value}
      </p>

      {description && (
        <p className="mt-2 text-sm text-slate-500">
          {description}
        </p>
      )}
    </div>
  );
}

export default function DashboardPage() {
  const router = useRouter();
  const { user, logout } = useAuth();

  const [isLoggingOut, setIsLoggingOut] =
    useState(false);

  if (!user) {
    return null;
  }

  const primaryBranch =
    user.branches.find(
      (branch) => branch.is_primary
    ) ?? user.branches[0];

  const primaryWarehouse =
    user.warehouses.find(
      (warehouse) => warehouse.is_primary
    ) ?? user.warehouses[0];

  const visiblePermissions =
    user.permissions.slice(0, 10);

  const remainingPermissionCount =
    user.permissions.length -
    visiblePermissions.length;

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
    <main className="min-h-screen bg-slate-100">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-5">
          <div className="flex items-center gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 font-bold text-white">
              DS
            </div>

            <div>
              <p className="font-bold text-slate-950">
                Desh Solar
              </p>

              <p className="text-xs text-slate-500">
                Inventory Management System
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={handleLogout}
            disabled={isLoggingOut}
            className="rounded-lg bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-500"
          >
            {isLoggingOut
              ? "Signing out..."
              : "Sign out"}
          </button>
        </div>
      </header>

      <div className="mx-auto max-w-7xl px-6 py-8">
        <section className="rounded-2xl bg-slate-950 p-7 text-white shadow-sm sm:p-8">
          <div className="flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
            <div>
              <p className="text-sm font-medium text-emerald-300">
                Welcome back
              </p>

              <h1 className="mt-2 text-3xl font-bold">
                {user.name}
              </h1>

              <p className="mt-2 text-slate-300">
                {user.email}
              </p>
            </div>

            <div className="rounded-xl border border-white/10 bg-white/5 px-5 py-4">
              <p className="text-xs uppercase tracking-wide text-slate-400">
                Company
              </p>

              <p className="mt-1 font-semibold">
                {user.company?.name ??
                  "Global access"}
              </p>

              {user.company && (
                <p className="mt-1 text-sm text-slate-400">
                  {user.company.code}
                </p>
              )}
            </div>
          </div>
        </section>

        <section className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <SummaryCard
            label="Primary branch"
            value={
              primaryBranch?.name ??
              "Not assigned"
            }
            description={primaryBranch?.code}
          />

          <SummaryCard
            label="Primary warehouse"
            value={
              primaryWarehouse?.name ??
              "Not assigned"
            }
            description={primaryWarehouse?.code}
          />

          <SummaryCard
            label="Assigned branches"
            value={String(user.branches.length)}
            description="Branches available to this account"
          />

          <SummaryCard
            label="Permissions"
            value={String(user.permissions.length)}
            description="Active authorization permissions"
          />
        </section>

        <div className="mt-6 grid gap-6 lg:grid-cols-2">
          <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
              <h2 className="text-lg font-bold text-slate-950">
                Assigned roles
              </h2>

              <p className="mt-1 text-sm text-slate-500">
                Roles determine the actions available to
                this account.
              </p>
            </div>

            <div className="mt-5 flex flex-wrap gap-2">
              {user.roles.length > 0 ? (
                user.roles.map((role) => (
                  <span
                    key={role.id}
                    className="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-800"
                  >
                    {role.name}
                  </span>
                ))
              ) : (
                <p className="text-sm text-slate-500">
                  No role is currently assigned.
                </p>
              )}
            </div>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
              <h2 className="text-lg font-bold text-slate-950">
                Permission overview
              </h2>

              <p className="mt-1 text-sm text-slate-500">
                Active permissions inherited from assigned
                roles.
              </p>
            </div>

            <div className="mt-5 flex flex-wrap gap-2">
              {visiblePermissions.length > 0 ? (
                <>
                  {visiblePermissions.map(
                    (permission) => (
                      <span
                        key={permission.code}
                        className="rounded-md bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-700"
                      >
                        {permission.code}
                      </span>
                    )
                  )}

                  {remainingPermissionCount > 0 && (
                    <span className="rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-medium text-white">
                      +{remainingPermissionCount} more
                    </span>
                  )}
                </>
              ) : (
                <p className="text-sm text-slate-500">
                  No active permissions found.
                </p>
              )}
            </div>
          </section>
        </div>

        <div className="mt-6 grid gap-6 lg:grid-cols-2">
          <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="flex items-center justify-between gap-4">
              <div>
                <h2 className="text-lg font-bold text-slate-950">
                  Assigned branches
                </h2>

                <p className="mt-1 text-sm text-slate-500">
                  Branches this account can access.
                </p>
              </div>

              <span className="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
                {user.branches.length}
              </span>
            </div>

            <div className="mt-5 space-y-3">
              {user.branches.length > 0 ? (
                user.branches.map((branch) => (
                  <div
                    key={branch.id}
                    className="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4"
                  >
                    <div>
                      <p className="font-semibold text-slate-900">
                        {branch.name}
                      </p>

                      <p className="mt-1 text-sm text-slate-500">
                        {branch.code}
                      </p>
                    </div>

                    {branch.is_primary && (
                      <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                        Primary
                      </span>
                    )}
                  </div>
                ))
              ) : (
                <p className="text-sm text-slate-500">
                  No branches are assigned.
                </p>
              )}
            </div>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="flex items-center justify-between gap-4">
              <div>
                <h2 className="text-lg font-bold text-slate-950">
                  Assigned warehouses
                </h2>

                <p className="mt-1 text-sm text-slate-500">
                  Warehouses this account can access.
                </p>
              </div>

              <span className="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
                {user.warehouses.length}
              </span>
            </div>

            <div className="mt-5 space-y-3">
              {user.warehouses.length > 0 ? (
                user.warehouses.map(
                  (warehouse) => (
                    <div
                      key={warehouse.id}
                      className="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4"
                    >
                      <div>
                        <p className="font-semibold text-slate-900">
                          {warehouse.name}
                        </p>

                        <p className="mt-1 text-sm text-slate-500">
                          {warehouse.code}
                        </p>
                      </div>

                      {warehouse.is_primary && (
                        <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                          Primary
                        </span>
                      )}
                    </div>
                  )
                )
              ) : (
                <p className="text-sm text-slate-500">
                  No warehouses are assigned.
                </p>
              )}
            </div>
          </section>
        </div>
      </div>
    </main>
  );
}