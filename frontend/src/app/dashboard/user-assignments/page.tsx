"use client";

import {
  type FormEvent,
  useEffect,
  useState,
} from "react";
import { AxiosError } from "axios";

import { UserAssignmentForm } from "@/components/user-assignments/UserAssignmentForm";
import { useAuth } from "@/contexts/AuthContext";
import { branchService } from "@/services/branchService";
import { userAssignmentService } from "@/services/userAssignmentService";
import { userService } from "@/services/userService";
import { warehouseService } from "@/services/warehouseService";
import type { ApiErrorResponse } from "@/types/auth";
import type { Branch } from "@/types/branch";
import type {
  User,
  UserPagination,
  UserQuery,
} from "@/types/user";
import type { UserAssignments } from "@/types/userAssignment";
import type { Warehouse } from "@/types/warehouse";

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

export default function UserAssignmentsPage() {
  const { user: authenticatedUser } =
    useAuth();

  const [users, setUsers] = useState<User[]>(
    []
  );

  const [branches, setBranches] =
    useState<Branch[]>([]);

  const [warehouses, setWarehouses] =
    useState<Warehouse[]>([]);

  const [pagination, setPagination] =
    useState<UserPagination>(
      emptyPagination
    );

  const [search, setSearch] = useState("");

  const [
    selectedUser,
    setSelectedUser,
  ] = useState<User | null>(null);

  const [
    assignments,
    setAssignments,
  ] = useState<UserAssignments | null>(
    null
  );

  const [isLoading, setIsLoading] =
    useState(true);

  const [
    isLoadingAssignments,
    setIsLoadingAssignments,
  ] = useState(false);

  const [
    isLoadingOptions,
    setIsLoadingOptions,
  ] = useState(false);

  const [
    isSavingBranches,
    setIsSavingBranches,
  ] = useState(false);

  const [
    isSavingWarehouses,
    setIsSavingWarehouses,
  ] = useState(false);

  const [
    branchErrors,
    setBranchErrors,
  ] = useState<Record<string, string>>({});

  const [
    warehouseErrors,
    setWarehouseErrors,
  ] = useState<Record<string, string>>({});

  const [
    errorMessage,
    setErrorMessage,
  ] = useState<string | null>(null);

  const [
    successMessage,
    setSuccessMessage,
  ] = useState<string | null>(null);

  const canViewUsers =
    authenticatedUser?.permissions.some(
      (permission) =>
        permission.code === "user.view"
    ) ?? false;

  const canManageAssignments =
    authenticatedUser?.permissions.some(
      (permission) =>
        permission.code === "user.update"
    ) ?? false;

  async function loadUsers(
    page = 1
  ): Promise<void> {
    setIsLoading(true);
    setErrorMessage(null);

    const query: UserQuery = {
      search: search.trim() || undefined,
      page,
      per_page: 10,
      sort_by: "name",
      sort_direction: "asc",
    };

    try {
      const data =
        await userService.getUsers(query);

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
      setIsLoading(false);
    }
  }

  useEffect(() => {
    if (!canViewUsers) {
      return;
    }

    let isMounted = true;

    userService
      .getUsers({
        page: 1,
        per_page: 10,
        sort_by: "name",
        sort_direction: "asc",
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
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [canViewUsers]);

  async function loadAssignmentOptions(): Promise<void> {
    if (!canManageAssignments) {
      return;
    }

    setIsLoadingOptions(true);

    try {
      const [branchData, warehouseData] =
        await Promise.all([
          branchService.getBranches({
            page: 1,
            per_page: 100,
            sort_by: "name",
            sort_direction: "asc",
          }),

          warehouseService.getWarehouses({
            page: 1,
            per_page: 100,
            sort_by: "name",
            sort_direction: "asc",
          }),
        ]);

      setBranches(
        branchData.branches.filter(
          (branch) => branch.is_active
        )
      );

      setWarehouses(
        warehouseData.warehouses.filter(
          (warehouse) =>
            warehouse.is_active
        )
      );
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to load available branches and warehouses."
        )
      );
    } finally {
      setIsLoadingOptions(false);
    }
  }

  async function handleSearch(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    await loadUsers(1);
  }

  async function handleSelectUser(
    user: User
  ): Promise<void> {
    setSelectedUser(user);
    setAssignments(null);
    setBranchErrors({});
    setWarehouseErrors({});
    setSuccessMessage(null);
    setIsLoadingAssignments(true);
    setErrorMessage(null);

    try {
      const assignmentPromise =
        userAssignmentService.getAssignments(
          user.id
        );

      if (canManageAssignments) {
        await loadAssignmentOptions();
      }

      const assignmentData =
        await assignmentPromise;

      setAssignments(assignmentData);
    } catch (error) {
      setErrorMessage(
        getApiMessage(
          error,
          "Unable to load user assignments."
        )
      );
    } finally {
      setIsLoadingAssignments(false);
    }
  }

  async function handleSaveBranches(
    branchIds: number[],
    primaryBranchId: number | null
  ): Promise<void> {
    if (!selectedUser) {
      return;
    }

    setIsSavingBranches(true);
    setBranchErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      const updatedAssignments =
        await userAssignmentService.syncBranchAssignments(
          selectedUser.id,
          {
            branch_ids: branchIds,
            primary_branch_id:
              primaryBranchId,
          }
        );

      setAssignments(updatedAssignments);

      setSuccessMessage(
        "Branch assignments saved successfully."
      );

      await loadUsers(
        pagination.current_page
      );
    } catch (error) {
      setBranchErrors(
        getValidationErrors(error)
      );

      setErrorMessage(
        getApiMessage(
          error,
          "Unable to save branch assignments."
        )
      );
    } finally {
      setIsSavingBranches(false);
    }
  }

  async function handleSaveWarehouses(
    warehouseIds: number[],
    primaryWarehouseId: number | null
  ): Promise<void> {
    if (!selectedUser) {
      return;
    }

    setIsSavingWarehouses(true);
    setWarehouseErrors({});
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      const updatedAssignments =
        await userAssignmentService.syncWarehouseAssignments(
          selectedUser.id,
          {
            warehouse_ids: warehouseIds,
            primary_warehouse_id:
              primaryWarehouseId,
          }
        );

      setAssignments(updatedAssignments);

      setSuccessMessage(
        "Warehouse assignments saved successfully."
      );

      await loadUsers(
        pagination.current_page
      );
    } catch (error) {
      setWarehouseErrors(
        getValidationErrors(error)
      );

      setErrorMessage(
        getApiMessage(
          error,
          "Unable to save warehouse assignments."
        )
      );
    } finally {
      setIsSavingWarehouses(false);
    }
  }

  function closeAssignmentPanel(): void {
    setSelectedUser(null);
    setAssignments(null);
    setBranchErrors({});
    setWarehouseErrors({});
    setSuccessMessage(null);
  }

  if (!canViewUsers) {
    return (
      <div className="rounded-2xl border border-red-200 bg-red-50 p-6">
        <h2 className="text-lg font-bold text-red-900">
          Access denied
        </h2>

        <p className="mt-2 text-sm text-red-700">
          You do not have permission to view
          user assignments.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 className="text-2xl font-bold text-slate-950">
          User Assignments
        </h2>

        <p className="mt-2 text-sm text-slate-500">
          Review users and manage their
          assigned branches and warehouses.
        </p>
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
          className="flex flex-col gap-4 border-b border-slate-200 p-5 sm:flex-row"
        >
          <input
            type="search"
            value={search}
            onChange={(event) =>
              setSearch(event.target.value)
            }
            placeholder="Search user name or email"
            className="h-11 flex-1 rounded-lg border border-slate-300 px-4 outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10"
          />

          <button
            type="submit"
            disabled={isLoading}
            className="h-11 rounded-lg bg-slate-950 px-6 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-500"
          >
            {isLoading
              ? "Searching..."
              : "Search"}
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
                  "Branches",
                  "Warehouses",
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
              {isLoading ? (
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
                users.map((user) => {
                  const isSelected =
                    selectedUser?.id === user.id;

                  return (
                    <tr
                      key={user.id}
                      className={
                        isSelected
                          ? "bg-emerald-50/50 align-top"
                          : "align-top"
                      }
                    >
                      <td className="px-5 py-4">
                        <p className="font-semibold text-slate-900">
                          {user.name}
                        </p>

                        <p className="mt-1 text-sm text-slate-500">
                          {user.email}
                        </p>
                      </td>

                      <td className="px-5 py-4">
                        {user.company ? (
                          <>
                            <p className="text-sm font-medium text-slate-700">
                              {user.company.name}
                            </p>

                            <p className="mt-1 text-xs text-slate-500">
                              {user.company.code}
                            </p>
                          </>
                        ) : (
                          <span className="text-sm text-slate-500">
                            Global user
                          </span>
                        )}
                      </td>

                      <td className="px-5 py-4">
                        <div className="flex max-w-xs flex-wrap gap-2">
                          {user.roles.length > 0 ? (
                            user.roles.map(
                              (role) => (
                                <span
                                  key={role.id}
                                  className="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
                                >
                                  {role.name}
                                </span>
                              )
                            )
                          ) : (
                            <span className="text-sm text-slate-500">
                              No role
                            </span>
                          )}
                        </div>
                      </td>

                      <td className="px-5 py-4 text-sm font-semibold text-slate-700">
                        {user.branches_count}
                      </td>

                      <td className="px-5 py-4 text-sm font-semibold text-slate-700">
                        {user.warehouses_count}
                      </td>

                      <td className="px-5 py-4">
                        <button
                          type="button"
                          onClick={() =>
                            void handleSelectUser(
                              user
                            )
                          }
                          disabled={
                            isLoadingAssignments &&
                            isSelected
                          }
                          className="rounded-md border border-emerald-300 px-3 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                          {isLoadingAssignments &&
                          isSelected
                            ? "Loading..."
                            : canManageAssignments
                              ? "Manage assignments"
                              : "View assignments"}
                        </button>
                      </td>
                    </tr>
                  );
                })
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
                isLoading ||
                pagination.current_page <= 1
              }
              onClick={() =>
                void loadUsers(
                  pagination.current_page - 1
                )
              }
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
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
                isLoading ||
                pagination.current_page >=
                  pagination.last_page
              }
              onClick={() =>
                void loadUsers(
                  pagination.current_page + 1
                )
              }
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </section>

      {selectedUser && (
        <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div>
              <h3 className="text-xl font-bold text-slate-950">
                {selectedUser.name}
              </h3>

              <p className="mt-1 text-sm text-slate-500">
                {selectedUser.email}
              </p>
            </div>

            <button
              type="button"
              onClick={closeAssignmentPanel}
              disabled={
                isSavingBranches ||
                isSavingWarehouses
              }
              className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Close
            </button>
          </div>

          {isLoadingAssignments ? (
            <div className="py-12 text-center text-sm text-slate-500">
              Loading assignments...
            </div>
          ) : assignments ? (
            <>
              <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <div className="rounded-xl border border-slate-200 p-5">
                  <div className="flex items-center justify-between">
                    <h4 className="font-bold text-slate-900">
                      Assigned branches
                    </h4>

                    <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                      {
                        assignments.branches
                          .length
                      }
                    </span>
                  </div>

                  <div className="mt-4 space-y-3">
                    {assignments.branches
                      .length === 0 ? (
                      <p className="text-sm text-slate-500">
                        No branches assigned.
                      </p>
                    ) : (
                      assignments.branches.map(
                        (branch) => (
                          <div
                            key={branch.id}
                            className="flex items-start justify-between rounded-lg bg-slate-50 p-3"
                          >
                            <div>
                              <p className="text-sm font-semibold text-slate-800">
                                {branch.name}
                              </p>

                              <p className="mt-1 text-xs text-slate-500">
                                {branch.code}
                              </p>
                            </div>

                            {branch.is_primary && (
                              <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">
                                Primary
                              </span>
                            )}
                          </div>
                        )
                      )
                    )}
                  </div>
                </div>

                <div className="rounded-xl border border-slate-200 p-5">
                  <div className="flex items-center justify-between">
                    <h4 className="font-bold text-slate-900">
                      Assigned warehouses
                    </h4>

                    <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                      {
                        assignments.warehouses
                          .length
                      }
                    </span>
                  </div>

                  <div className="mt-4 space-y-3">
                    {assignments.warehouses
                      .length === 0 ? (
                      <p className="text-sm text-slate-500">
                        No warehouses assigned.
                      </p>
                    ) : (
                      assignments.warehouses.map(
                        (warehouse) => (
                          <div
                            key={warehouse.id}
                            className="flex items-start justify-between rounded-lg bg-slate-50 p-3"
                          >
                            <div>
                              <p className="text-sm font-semibold text-slate-800">
                                {warehouse.name}
                              </p>

                              <p className="mt-1 text-xs text-slate-500">
                                {
                                  warehouse
                                    .branch.name
                                }
                              </p>
                            </div>

                            {warehouse.is_primary && (
                              <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">
                                Primary
                              </span>
                            )}
                          </div>
                        )
                      )
                    )}
                  </div>
                </div>
              </div>

              {canManageAssignments &&
                (isLoadingOptions ? (
                  <div className="mt-6 rounded-xl border border-slate-200 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">
                    Loading available branches
                    and warehouses...
                  </div>
                ) : (
                  <UserAssignmentForm
                    key={[
                      assignments.id,
                      assignments.branches
                        .map(
                          (branch) =>
                            `${branch.id}:${branch.is_primary}`
                        )
                        .join(","),
                      assignments.warehouses
                        .map(
                          (warehouse) =>
                            `${warehouse.id}:${warehouse.is_primary}`
                        )
                        .join(","),
                    ].join("-")}
                    assignments={assignments}
                    branches={branches}
                    warehouses={warehouses}
                    isSavingBranches={
                      isSavingBranches
                    }
                    isSavingWarehouses={
                      isSavingWarehouses
                    }
                    branchErrors={
                      branchErrors
                    }
                    warehouseErrors={
                      warehouseErrors
                    }
                    onSaveBranches={
                      handleSaveBranches
                    }
                    onSaveWarehouses={
                      handleSaveWarehouses
                    }
                  />
                ))}
            </>
          ) : null}
        </section>
      )}
    </div>
  );
}