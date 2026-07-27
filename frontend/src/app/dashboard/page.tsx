"use client";

import { useAuth } from "@/contexts/AuthContext";

interface SummaryCardProps {
  label: string;
  value: string;
  description: string;
}

function SummaryCard({
  label,
  value,
  description,
}: SummaryCardProps) {
  return (
    <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <p className="text-sm font-medium text-slate-500">
        {label}
      </p>

      <p className="mt-2 text-2xl font-bold text-slate-950">
        {value}
      </p>

      <p className="mt-2 text-sm text-slate-500">
        {description}
      </p>
    </article>
  );
}

export default function DashboardPage() {
  const { user } = useAuth();

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

  return (
    <div className="space-y-6">
      <section className="overflow-hidden rounded-2xl bg-slate-950 p-7 text-white shadow-sm sm:p-8">
        <p className="text-sm font-medium text-emerald-300">
          Welcome back
        </p>

        <h2 className="mt-2 text-3xl font-bold">
          {user.name}
        </h2>

        <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
          Review your assigned company, branches,
          warehouses and available inventory management
          permissions.
        </p>
      </section>

      <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <SummaryCard
          label="Company"
          value={user.company?.name ?? "Global"}
          description={
            user.company?.code ??
            "System-wide access"
          }
        />

        <SummaryCard
          label="Primary branch"
          value={
            primaryBranch?.name ??
            "Not assigned"
          }
          description={
            primaryBranch?.code ??
            "No primary branch"
          }
        />

        <SummaryCard
          label="Primary warehouse"
          value={
            primaryWarehouse?.name ??
            "Not assigned"
          }
          description={
            primaryWarehouse?.code ??
            "No primary warehouse"
          }
        />

        <SummaryCard
          label="Permissions"
          value={String(user.permissions.length)}
          description="Active permissions available"
        />
      </section>

      <section className="grid gap-6 xl:grid-cols-2">
        <article className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="flex items-center justify-between gap-4">
            <div>
              <h3 className="text-lg font-bold text-slate-950">
                Assigned branches
              </h3>

              <p className="mt-1 text-sm text-slate-500">
                Branches currently accessible to you.
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
        </article>

        <article className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="flex items-center justify-between gap-4">
            <div>
              <h3 className="text-lg font-bold text-slate-950">
                Assigned warehouses
              </h3>

              <p className="mt-1 text-sm text-slate-500">
                Warehouses currently accessible to you.
              </p>
            </div>

            <span className="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
              {user.warehouses.length}
            </span>
          </div>

          <div className="mt-5 space-y-3">
            {user.warehouses.length > 0 ? (
              user.warehouses.map((warehouse) => (
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
              ))
            ) : (
              <p className="text-sm text-slate-500">
                No warehouses are assigned.
              </p>
            )}
          </div>
        </article>
      </section>
    </div>
  );
}