"use client";

import {
  type FormEvent,
  useEffect,
  useState,
} from "react";
import { AxiosError } from "axios";
import Link from "next/link";

import { UserForm } from "@/components/users/UserForm";
import { useAuth } from "@/contexts/AuthContext";
import { companyService } from "@/services/companyService";
import { roleService } from "@/services/roleService";
import { userService } from "@/services/userService";
import type { ApiErrorResponse } from "@/types/auth";
import type { Company } from "@/types/company";
import type { Role } from "@/types/role";
import type {
  CreateUserPayload,
  UpdateUserPayload,
  User,
  UserPagination,
  UserQuery,
} from "@/types/user";

const emptyPagination: UserPagination = {
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: null,
  to: null,
};

function getApiMessage(
  error: unknown,
  fallback: string
): string {
  const axiosError =
    error as AxiosError<ApiErrorResponse>;

  return (
    axiosError.response?.data?.message ??
    fallback
  );
}

function getValidationErrors(
  error: unknown
): Record<string, string> {
  const axiosError =
    error as AxiosError<ApiErrorResponse>;

  const errors =
    axiosError.response?.data?.errors ?? {};

  return Object.fromEntries(
    Object.entries(errors).map(
      ([field, messages]) => [
        field,
        messages[0] ??
          "The provided value is invalid.",
      ]
    )
  );
}

function formatDate(
  value: string | null
): string {
  if (!value) {
    return "—";
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return "—";
  }

  return new Intl.DateTimeFormat(
    "en-BD",
    {
      year: "numeric",
      month: "short",
      day: "2-digit",
    }
  ).format(date);
}

export default function UsersPage() {
  const { user: authenticatedUser } =
    useAuth();

  const [users, setUsers] =
    useState<User[]>([]);

  const [roles, setRoles] =
    useState<Role[]>([]);

  const [companies, setCompanies] =
    useState<Company[]>([]);

  const [
    editingUser,
    setEditingUser,
  ] = useState<User | null>(null);

  const [pagination, setPagination] =
    useState<UserPagination>(
      emptyPagination
    );

  const [search, setSearch] =
    useState("");

  const [sortBy, setSortBy] =
    useState<UserQuery["sort_by"]>(
      "name"
    );

  const [
    sortDirection,
    setSortDirection,
  ] = useState<
    UserQuery["sort_direction"]
  >("asc");

  const [
    isUserFormOpen,
    setIsUserFormOpen,
  ] = useState(false);

  const [
    isUserLoading,
    setIsUserLoading,
  ] = useState(true);

  const [
    isRoleLoading,
    setIsRoleLoading,
  ] = useState(false);

  const [
    isUserSaving,
    setIsUserSaving,
  ] = useState(false);

  const [
    formErrors,
    setFormErrors,
  ] = useState<Record<string, string>>(
    {}
  );

  const [
    errorMessage,
    setErrorMessage,
  ] = useState<string | null>(null);

  const [
    successMessage,
    setSuccessMessage,
  ] = useState<string | null>(null);

  const isSuperAdmin =
    authenticatedUser?.roles.some(
      (role) =>
        role.code === "SUPER-ADMIN"
    ) ?? false;

  const canCreateUser =
    isSuperAdmin ||
    (authenticatedUser?.permissions.some(
      (permission) =>
        permission.code ===
        "user.create"
    ) ??
      false);

  const canUpdateUser =
    isSuperAdmin ||
    (authenticatedUser?.permissions.some(
      (permission) =>
        permission.code ===
        "user.update"
    ) ??
      false);

  async function loadUsers(
    page = 1
  ): Promise<void> {
    setIsUserLoading(true);
    setErrorMessage(null);

    try {
      const data =
        await userService.getUsers({
          search:
            search.trim() || undefined,

          sort_by: sortBy,
          sort_direction: sortDirection,
          per_page: 10,
          page,
        });

      setUsers(data.users);
      setPagination(data.pagination);
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to load users."
        )
      );
    } finally {
      setIsUserLoading(false);
    }
  }

  async function loadRoles(
    companyId?: number | null
  ): Promise<void> {
    setIsRoleLoading(true);
    setFormErrors({});

    try {
      const loadedRoles =
        await roleService.getRoles(
          companyId
            ? {
                company_id: companyId,
              }
            : {}
        );

      setRoles(loadedRoles);
    } catch (error) {
      setRoles([]);

      setErrorMessage(
        getApiMessage(
          error,
          "Unable to load assignable roles."
        )
      );
    } finally {
      setIsRoleLoading(false);
    }
  }

  useEffect(() => {
    let isMounted = true;

    userService
      .getUsers({
        sort_by: "name",
        sort_direction: "asc",
        per_page: 10,
        page: 1,
      })
      .then((data) => {
        if (!isMounted) {
          return;
        }

        setUsers(data.users);
        setPagination(data.pagination);
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return;
        }

        setErrorMessage(
          getApiMessage(
            error,
            "Unable to load users."
          )
        );
      })
      .finally(() => {
        if (isMounted) {
          setIsUserLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, []);

  useEffect(() => {
    if (!isSuperAdmin) {
      return;
    }

    let isMounted = true;

    companyService
      .getAccessibleCompanies()
      .then((loadedCompanies) => {
        if (!isMounted) {
          return;
        }

        setCompanies(
          loadedCompanies.filter(
            (company) =>
              company.is_active
          )
        );
      })
      .catch((error: unknown) => {
        if (!isMounted) {
          return;
        }

        setErrorMessage(
          getApiMessage(
            error,
            "Unable to load companies."
          )
        );
      });

    return () => {
      isMounted = false;
    };
  }, [isSuperAdmin]);

  async function handleSearch(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    await loadUsers(1);
  }

  async function openCreateUserForm(): Promise<void> {
    setEditingUser(null);
    setFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setRoles([]);
    setIsUserFormOpen(true);

    await loadRoles(null);
  }

  async function openEditUserForm(
    selectedUser: User
  ): Promise<void> {
    setEditingUser(selectedUser);
    setFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);
    setRoles([]);
    setIsUserFormOpen(true);

    await loadRoles(
      selectedUser.company_id
    );
  }

  function closeUserForm(): void {
    if (isUserSaving) {
      return;
    }

    setIsUserFormOpen(false);
    setEditingUser(null);
    setRoles([]);
    setFormErrors({});
  }

  async function handleCompanyChange(
    companyId: number | null
  ): Promise<void> {
    await loadRoles(companyId);
  }

  async function handleSaveUser(
    payload:
      | CreateUserPayload
      | UpdateUserPayload
  ): Promise<void> {
    setIsUserSaving(true);
    setFormErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      if (editingUser) {
        await userService.updateUser(
          editingUser.id,
          payload as UpdateUserPayload
        );

        setSuccessMessage(
          "User updated successfully."
        );
      } else {
        await userService.createUser(
          payload as CreateUserPayload
        );

        setSuccessMessage(
          "User created successfully."
        );
      }

      setIsUserFormOpen(false);
      setEditingUser(null);
      setRoles([]);

      await loadUsers(
        pagination.current_page
      );
    } catch (error) {
      setFormErrors(
        getValidationErrors(error)
      );

      setErrorMessage(
        getApiMessage(
          error,
          editingUser
            ? "Unable to update the user."
            : "Unable to create the user."
        )
      );
    } finally {
      setIsUserSaving(false);
    }
  }

  return (
    <div className="space-y-6">
      <section className="flex flex-col justify-between gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-950">
            User Management
          </h2>

          <p className="mt-2 text-sm leading-6 text-slate-500">
            Create internal accounts,
            review assigned roles, and
            manage branch and warehouse
            access.
          </p>
        </div>

        {canCreateUser && (
          <button
            type="button"
            onClick={() =>
              void openCreateUserForm()
            }
            className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
          >
            Add user
          </button>
        )}
      </section>

      {successMessage && (
        <div
          role="status"
          className="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800"
        >
          {successMessage}
        </div>
      )}

      {errorMessage && (
        <div
          role="alert"
          className="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"
        >
          {errorMessage}
        </div>
      )}

      <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form
          onSubmit={handleSearch}
          className="grid gap-4 border-b border-slate-200 p-5 lg:grid-cols-[1fr_190px_170px_auto]"
        >
          <input
            type="search"
            value={search}
            onChange={(event) =>
              setSearch(event.target.value)
            }
            placeholder="Search name or email"
            className="h-11 rounded-lg border border-slate-300 bg-white px-4 text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
          />

          <select
            value={sortBy}
            onChange={(event) =>
              setSortBy(
                event.target
                  .value as UserQuery["sort_by"]
              )
            }
            className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
          >
            <option value="name">
              Sort by name
            </option>

            <option value="email">
              Sort by email
            </option>

            <option value="created_at">
              Sort by created date
            </option>

            <option value="updated_at">
              Sort by updated date
            </option>
          </select>

          <select
            value={sortDirection}
            onChange={(event) =>
              setSortDirection(
                event.target
                  .value as UserQuery["sort_direction"]
              )
            }
            className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
          >
            <option value="asc">
              Ascending
            </option>

            <option value="desc">
              Descending
            </option>
          </select>

          <button
            type="submit"
            disabled={isUserLoading}
            className="h-11 rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-500"
          >
            Search
          </button>
        </form>

        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200">
            <thead className="bg-slate-50">
              <tr>
                {[
                  "User",
                  "Company",
                  "Roles",
                  "Assignments",
                  "Created",
                  "Actions",
                ].map((heading) => (
                  <th
                    key={heading}
                    className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                  >
                    {heading}
                  </th>
                ))}
              </tr>
            </thead>

            <tbody className="divide-y divide-slate-200 bg-white">
              {isUserLoading ? (
                <tr>
                  <td
                    colSpan={6}
                    className="px-5 py-12 text-center text-sm text-slate-500"
                  >
                    Loading users...
                  </td>
                </tr>
              ) : users.length === 0 ? (
                <tr>
                  <td
                    colSpan={6}
                    className="px-5 py-12 text-center text-sm text-slate-500"
                  >
                    No users found.
                  </td>
                </tr>
              ) : (
                users.map((user) => (
                  <tr
                    key={user.id}
                    className="align-top"
                  >
                    <td className="px-5 py-4">
                      <div className="flex items-start gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 font-semibold text-emerald-800">
                          {user.name
                            .charAt(0)
                            .toUpperCase()}
                        </div>

                        <div className="min-w-0">
                          <p className="font-semibold text-slate-900">
                            {user.name}
                          </p>

                          <p className="mt-1 break-all text-sm text-slate-500">
                            {user.email}
                          </p>
                        </div>
                      </div>
                    </td>

                    <td className="px-5 py-4">
                      {user.company ? (
                        <>
                          <p className="text-sm font-medium text-slate-700">
                            {
                              user.company
                                .name
                            }
                          </p>

                          <p className="mt-1 text-xs text-slate-500">
                            {
                              user.company
                                .code
                            }
                          </p>
                        </>
                      ) : (
                        <span className="inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800">
                          Global access
                        </span>
                      )}
                    </td>

                    <td className="px-5 py-4">
                      {user.roles.length > 0 ? (
                        <div className="flex max-w-xs flex-wrap gap-2">
                          {user.roles.map(
                            (role) => (
                              <span
                                key={role.id}
                                className="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800"
                              >
                                {role.name}
                              </span>
                            )
                          )}
                        </div>
                      ) : (
                        <span className="text-sm text-amber-700">
                          No role assigned
                        </span>
                      )}
                    </td>

                    <td className="px-5 py-4">
                      <p className="text-sm font-medium text-slate-700">
                        {
                          user.branches_count
                        }{" "}
                        branches
                      </p>

                      <p className="mt-1 text-sm text-slate-500">
                        {
                          user.warehouses_count
                        }{" "}
                        warehouses
                      </p>
                    </td>

                    <td className="px-5 py-4 text-sm text-slate-600">
                      {formatDate(
                        user.created_at
                      )}
                    </td>

                    <td className="px-5 py-4">
                      <div className="flex flex-wrap gap-2">
                        <Link
                          href={`/dashboard/user-assignments?user_id=${user.id}`}
                          className="rounded-md border border-blue-300 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50"
                        >
                          Assign access
                        </Link>

                        {canUpdateUser ? (
                          <button
                            type="button"
                            onClick={() =>
                              void openEditUserForm(
                                user
                              )
                            }
                            className="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                          >
                            Edit
                          </button>
                        ) : (
                          <span className="text-xs text-slate-400">
                            View only
                          </span>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="flex flex-col justify-between gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
          <p className="text-sm text-slate-500">
            Showing {pagination.from ?? 0}–
            {pagination.to ?? 0} of{" "}
            {pagination.total} users
          </p>

          <div className="flex gap-2">
            <button
              type="button"
              disabled={
                isUserLoading ||
                pagination.current_page <= 1
              }
              onClick={() =>
                void loadUsers(
                  pagination.current_page -
                    1
                )
              }
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Previous
            </button>

            <span className="flex items-center px-3 text-sm text-slate-600">
              Page{" "}
              {pagination.current_page} of{" "}
              {pagination.last_page}
            </span>

            <button
              type="button"
              disabled={
                isUserLoading ||
                pagination.current_page >=
                  pagination.last_page
              }
              onClick={() =>
                void loadUsers(
                  pagination.current_page +
                    1
                )
              }
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </section>

      {isUserFormOpen && (
        <UserForm
          key={
            editingUser?.id ??
            "new-user"
          }
          user={editingUser}
          roles={roles}
          companies={companies}
          isSuperAdmin={isSuperAdmin}
          isSaving={isUserSaving}
          isLoadingRoles={isRoleLoading}
          errors={formErrors}
          onCompanyChange={
            handleCompanyChange
          }
          onCancel={closeUserForm}
          onSubmit={handleSaveUser}
        />
      )}
    </div>
  );
}