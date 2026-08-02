"use client";

import {
  type ReactNode,
  useMemo,
  useState,
} from "react";
import Link from "next/link";
import {
  usePathname,
  useRouter,
} from "next/navigation";

import { useAuth } from "@/contexts/AuthContext";

type NavigationIconName =
  | "dashboard"
  | "company"
  | "branch"
  | "warehouse"
  | "supplier"
  | "users";

interface NavigationItem {
  label: string;
  href: string;
  permission?: string;
  icon: NavigationIconName;
}

interface DashboardShellProps {
  children: ReactNode;
}

const navigationItems: NavigationItem[] = [
  {
    label: "Dashboard",
    href: "/dashboard",
    icon: "dashboard",
  },
  {
    label: "Company",
    href: "/dashboard/company",
    permission: "company.view",
    icon: "company",
  },
  {
    label: "Branches",
    href: "/dashboard/branches",
    permission: "branch.view",
    icon: "branch",
  },
  {
    label: "Warehouses",
    href: "/dashboard/warehouses",
    permission: "warehouse.view",
    icon: "warehouse",
  },
  {
    label: "Suppliers",
    href: "/dashboard/suppliers",
    permission: "supplier.view",
    icon: "supplier",
  },
  {
    label: "Users",
    href: "/dashboard/users",
    permission: "user.view",
    icon: "users",
  },
  {
    label: "User Assignments",
    href: "/dashboard/user-assignments",
    permission: "user.view",
    icon: "users",
  },
];

function NavigationIcon({
  name,
}: {
  name: NavigationIconName;
}) {
  if (name === "dashboard") {
    return (
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        className="h-5 w-5"
        aria-hidden="true"
      >
        <rect
          x="3"
          y="3"
          width="7"
          height="7"
          rx="1"
        />

        <rect
          x="14"
          y="3"
          width="7"
          height="7"
          rx="1"
        />

        <rect
          x="3"
          y="14"
          width="7"
          height="7"
          rx="1"
        />

        <rect
          x="14"
          y="14"
          width="7"
          height="7"
          rx="1"
        />
      </svg>
    );
  }

  if (name === "company") {
    return (
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        className="h-5 w-5"
        aria-hidden="true"
      >
        <path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16" />

        <path d="M16 9h2a2 2 0 0 1 2 2v10" />

        <path d="M8 7h4M8 11h4M8 15h4M3 21h18" />
      </svg>
    );
  }

  if (name === "branch") {
    return (
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        className="h-5 w-5"
        aria-hidden="true"
      >
        <circle
          cx="6"
          cy="5"
          r="2"
        />

        <circle
          cx="18"
          cy="7"
          r="2"
        />

        <circle
          cx="18"
          cy="17"
          r="2"
        />

        <path d="M8 5h3a3 3 0 0 1 3 3v6a3 3 0 0 0 3 3" />

        <path d="M14 10a3 3 0 0 1 3-3" />
      </svg>
    );
  }

  if (name === "warehouse") {
    return (
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        className="h-5 w-5"
        aria-hidden="true"
      >
        <path d="M3 21V8l9-5 9 5v13" />

        <path d="M7 21v-8h10v8M7 10h10" />

        <path d="M10 17h4" />
      </svg>
    );
  }

  if (name === "supplier") {
    return (
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        className="h-5 w-5"
        aria-hidden="true"
      >
        <path d="M3 7h11v10H3z" />

        <path d="M14 10h3l4 4v3h-7z" />

        <circle
          cx="7"
          cy="19"
          r="2"
        />

        <circle
          cx="18"
          cy="19"
          r="2"
        />

        <path d="M5 7V5h7v2" />
      </svg>
    );
  }

  if (name === "users") {
    return (
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        className="h-5 w-5"
        aria-hidden="true"
      >
        <circle
          cx="9"
          cy="8"
          r="3"
        />

        <path d="M3.5 20a5.5 5.5 0 0 1 11 0" />

        <circle
          cx="17"
          cy="9"
          r="2.5"
        />

        <path d="M15.5 14.5A5 5 0 0 1 21 20" />
      </svg>
    );
  }

  return null;
}

export function DashboardShell({
  children,
}: DashboardShellProps) {
  const pathname = usePathname();
  const router = useRouter();

  const { user, logout } = useAuth();

  const [
    isSidebarOpen,
    setIsSidebarOpen,
  ] = useState(false);

  const [
    isLoggingOut,
    setIsLoggingOut,
  ] = useState(false);

  const permissionCodes = useMemo(
    () =>
      new Set(
        user?.permissions.map(
          (permission) =>
            permission.code
        ) ?? []
      ),
    [user]
  );

  const visibleNavigationItems =
    useMemo(
      () =>
        navigationItems.filter(
          (item) =>
            !item.permission ||
            permissionCodes.has(
              item.permission
            )
        ),
      [permissionCodes]
    );

  const currentNavigationItem =
    visibleNavigationItems.find(
      (item) => {
        if (
          item.href === "/dashboard"
        ) {
          return pathname === "/dashboard";
        }

        return pathname.startsWith(
          item.href
        );
      }
    );

  const pageTitle =
    currentNavigationItem?.label ??
    "Dashboard";

  const primaryRole =
    user?.roles[0]?.name ??
    "No role assigned";

  function isActive(
    href: string
  ): boolean {
    if (href === "/dashboard") {
      return pathname === "/dashboard";
    }

    return pathname.startsWith(href);
  }

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

  if (!user) {
    return null;
  }

  return (
    <div className="min-h-screen bg-slate-100">
      {isSidebarOpen && (
        <button
          type="button"
          aria-label="Close navigation"
          onClick={() =>
            setIsSidebarOpen(false)
          }
          className="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
        />
      )}

      <aside
        className={`fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-800 bg-slate-950 text-white transition-transform duration-200 lg:translate-x-0 ${
          isSidebarOpen
            ? "translate-x-0"
            : "-translate-x-full"
        }`}
      >
        <div className="flex h-20 items-center justify-between border-b border-slate-800 px-5">
          <Link
            href="/dashboard"
            onClick={() =>
              setIsSidebarOpen(false)
            }
            className="flex items-center gap-3"
          >
            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500 font-bold text-white">
              DS
            </div>

            <div>
              <p className="font-bold text-white">
                Desh Solar
              </p>

              <p className="text-xs text-slate-400">
                Inventory Management
              </p>
            </div>
          </Link>

          <button
            type="button"
            aria-label="Close sidebar"
            onClick={() =>
              setIsSidebarOpen(false)
            }
            className="rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white lg:hidden"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              className="h-5 w-5"
              aria-hidden="true"
            >
              <path d="m6 6 12 12M18 6 6 18" />
            </svg>
          </button>
        </div>

        <div className="flex-1 overflow-y-auto px-4 py-6">
          <p className="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
            Main navigation
          </p>

          <nav
            aria-label="Dashboard navigation"
            className="mt-3 space-y-1"
          >
            {visibleNavigationItems.map(
              (item) => {
                const active =
                  isActive(item.href);

                return (
                  <Link
                    key={item.href}
                    href={item.href}
                    onClick={() =>
                      setIsSidebarOpen(
                        false
                      )
                    }
                    className={`flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition ${
                      active
                        ? "bg-emerald-500 text-white"
                        : "text-slate-300 hover:bg-slate-800 hover:text-white"
                    }`}
                  >
                    <NavigationIcon
                      name={item.icon}
                    />

                    <span>
                      {item.label}
                    </span>
                  </Link>
                );
              }
            )}
          </nav>
        </div>

        <div className="border-t border-slate-800 p-4">
          <div className="rounded-xl bg-slate-900 p-4">
            <div className="flex items-start gap-3">
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 font-semibold text-white">
                {user.name
                  .charAt(0)
                  .toUpperCase()}
              </div>

              <div className="min-w-0">
                <p className="truncate text-sm font-semibold text-white">
                  {user.name}
                </p>

                <p className="mt-0.5 truncate text-xs text-slate-400">
                  {primaryRole}
                </p>

                <p className="mt-0.5 truncate text-xs text-slate-500">
                  {user.company?.name ??
                    "Global access"}
                </p>
              </div>
            </div>

            <button
              type="button"
              onClick={handleLogout}
              disabled={isLoggingOut}
              className="mt-4 flex w-full items-center justify-center rounded-lg border border-slate-700 px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:border-slate-600 hover:bg-slate-800 hover:text-white disabled:cursor-not-allowed disabled:opacity-60"
            >
              {isLoggingOut
                ? "Signing out..."
                : "Sign out"}
            </button>
          </div>
        </div>
      </aside>

      <div className="lg:pl-72">
        <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
          <div className="flex h-20 items-center justify-between gap-5 px-4 sm:px-6 lg:px-8">
            <div className="flex items-center gap-4">
              <button
                type="button"
                aria-label="Open sidebar"
                onClick={() =>
                  setIsSidebarOpen(true)
                }
                className="rounded-lg border border-slate-200 p-2.5 text-slate-600 transition hover:bg-slate-100 lg:hidden"
              >
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  className="h-5 w-5"
                  aria-hidden="true"
                >
                  <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </button>

              <div>
                <h1 className="text-xl font-bold text-slate-950">
                  {pageTitle}
                </h1>

                <p className="mt-0.5 hidden text-sm text-slate-500 sm:block">
                  {user.company?.name ??
                    "System administration"}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <div className="hidden text-right sm:block">
                <p className="text-sm font-semibold text-slate-900">
                  {user.name}
                </p>

                <p className="text-xs text-slate-500">
                  {primaryRole}
                </p>
              </div>

              <div className="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 font-semibold text-emerald-800">
                {user.name
                  .charAt(0)
                  .toUpperCase()}
              </div>
            </div>
          </div>
        </header>

        <main className="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
          {children}
        </main>
      </div>
    </div>
  );
}