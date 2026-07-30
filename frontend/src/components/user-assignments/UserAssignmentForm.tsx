"use client";

import {
  type FormEvent,
  useMemo,
  useState,
} from "react";

import type { Branch } from "@/types/branch";
import type { Warehouse } from "@/types/warehouse";
import type { UserAssignments } from "@/types/userAssignment";

interface UserAssignmentFormProps {
  assignments: UserAssignments;
  branches: Branch[];
  warehouses: Warehouse[];
  isSavingBranches: boolean;
  isSavingWarehouses: boolean;
  branchErrors: Record<string, string>;
  warehouseErrors: Record<string, string>;
  onSaveBranches: (
    branchIds: number[],
    primaryBranchId: number | null
  ) => Promise<void>;
  onSaveWarehouses: (
    warehouseIds: number[],
    primaryWarehouseId: number | null
  ) => Promise<void>;
}

export function UserAssignmentForm({
  assignments,
  branches,
  warehouses,
  isSavingBranches,
  isSavingWarehouses,
  branchErrors,
  warehouseErrors,
  onSaveBranches,
  onSaveWarehouses,
}: UserAssignmentFormProps) {
  const [selectedBranchIds, setSelectedBranchIds] =
    useState<number[]>(
      assignments.branches.map(
        (branch) => branch.id
      )
    );

  const [
    primaryBranchId,
    setPrimaryBranchId,
  ] = useState<number | null>(
    assignments.branches.find(
      (branch) => branch.is_primary
    )?.id ?? null
  );

  const [
    selectedWarehouseIds,
    setSelectedWarehouseIds,
  ] = useState<number[]>(
    assignments.warehouses.map(
      (warehouse) => warehouse.id
    )
  );

  const [
    primaryWarehouseId,
    setPrimaryWarehouseId,
  ] = useState<number | null>(
    assignments.warehouses.find(
      (warehouse) => warehouse.is_primary
    )?.id ?? null
  );

  const activeBranches = useMemo(
    () =>
      branches.filter(
        (branch) => branch.is_active
      ),
    [branches]
  );

  const availableWarehouses = useMemo(
    () =>
      warehouses.filter(
        (warehouse) =>
          warehouse.is_active &&
          selectedBranchIds.includes(
            warehouse.branch.id
          )
      ),
    [warehouses, selectedBranchIds]
  );

  function toggleBranch(
    branchId: number
  ): void {
    setSelectedBranchIds(
      (currentBranchIds) => {
        const isSelected =
          currentBranchIds.includes(branchId);

        if (isSelected) {
          const nextBranchIds =
            currentBranchIds.filter(
              (id) => id !== branchId
            );

          if (primaryBranchId === branchId) {
            setPrimaryBranchId(null);
          }

          const removedWarehouseIds =
            warehouses
              .filter(
                (warehouse) =>
                  warehouse.branch.id ===
                  branchId
              )
              .map(
                (warehouse) => warehouse.id
              );

          setSelectedWarehouseIds(
            (currentWarehouseIds) =>
              currentWarehouseIds.filter(
                (warehouseId) =>
                  !removedWarehouseIds.includes(
                    warehouseId
                  )
              )
          );

          if (
            primaryWarehouseId !== null &&
            removedWarehouseIds.includes(
              primaryWarehouseId
            )
          ) {
            setPrimaryWarehouseId(null);
          }

          return nextBranchIds;
        }

        return [
          ...currentBranchIds,
          branchId,
        ];
      }
    );
  }

  function toggleWarehouse(
    warehouseId: number
  ): void {
    setSelectedWarehouseIds(
      (currentWarehouseIds) => {
        const isSelected =
          currentWarehouseIds.includes(
            warehouseId
          );

        if (isSelected) {
          if (
            primaryWarehouseId === warehouseId
          ) {
            setPrimaryWarehouseId(null);
          }

          return currentWarehouseIds.filter(
            (id) => id !== warehouseId
          );
        }

        return [
          ...currentWarehouseIds,
          warehouseId,
        ];
      }
    );
  }

  function handlePrimaryBranchChange(
    value: string
  ): void {
    setPrimaryBranchId(
      value ? Number(value) : null
    );
  }

  function handlePrimaryWarehouseChange(
    value: string
  ): void {
    setPrimaryWarehouseId(
      value ? Number(value) : null
    );
  }

  async function handleBranchSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    await onSaveBranches(
      selectedBranchIds,
      primaryBranchId
    );
  }

  async function handleWarehouseSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    await onSaveWarehouses(
      selectedWarehouseIds,
      primaryWarehouseId
    );
  }

  return (
    <div className="mt-6 grid gap-6 xl:grid-cols-2">
      <form
        onSubmit={handleBranchSubmit}
        className="rounded-xl border border-slate-200 bg-white p-5"
      >
        <div>
          <h4 className="font-bold text-slate-900">
            Manage branch assignments
          </h4>

          <p className="mt-1 text-sm text-slate-500">
            Select the branches this user can
            access and choose one primary
            branch.
          </p>
        </div>

        {branchErrors.branch_ids && (
          <p className="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {branchErrors.branch_ids}
          </p>
        )}

        {branchErrors.primary_branch_id && (
          <p className="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {
              branchErrors.primary_branch_id
            }
          </p>
        )}

        <div className="mt-5 max-h-80 space-y-3 overflow-y-auto pr-1">
          {activeBranches.length === 0 ? (
            <p className="text-sm text-slate-500">
              No active branches are
              available.
            </p>
          ) : (
            activeBranches.map((branch) => {
              const isSelected =
                selectedBranchIds.includes(
                  branch.id
                );

              return (
                <label
                  key={branch.id}
                  className={`flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition ${
                    isSelected
                      ? "border-emerald-300 bg-emerald-50"
                      : "border-slate-200 hover:bg-slate-50"
                  }`}
                >
                  <input
                    type="checkbox"
                    checked={isSelected}
                    onChange={() =>
                      toggleBranch(branch.id)
                    }
                    disabled={
                      isSavingBranches ||
                      isSavingWarehouses
                    }
                    className="mt-1 h-4 w-4 accent-emerald-600"
                  />

                  <span className="min-w-0 flex-1">
                    <span className="block text-sm font-semibold text-slate-800">
                      {branch.name}
                    </span>

                    <span className="mt-1 block text-xs text-slate-500">
                      {branch.code}
                      {branch.city
                        ? ` · ${branch.city}`
                        : ""}
                    </span>
                  </span>

                  {branch.is_head_office && (
                    <span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                      Head office
                    </span>
                  )}
                </label>
              );
            })
          )}
        </div>

        <div className="mt-5">
          <label
            htmlFor="primary-branch"
            className="mb-2 block text-sm font-semibold text-slate-700"
          >
            Primary branch
          </label>

          <select
            id="primary-branch"
            value={primaryBranchId ?? ""}
            onChange={(event) =>
              handlePrimaryBranchChange(
                event.target.value
              )
            }
            disabled={
              selectedBranchIds.length ===
                0 || isSavingBranches
            }
            className="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 outline-none focus:border-emerald-600 disabled:cursor-not-allowed disabled:bg-slate-100"
          >
            <option value="">
              No primary branch
            </option>

            {activeBranches
              .filter((branch) =>
                selectedBranchIds.includes(
                  branch.id
                )
              )
              .map((branch) => (
                <option
                  key={branch.id}
                  value={branch.id}
                >
                  {branch.name}
                </option>
              ))}
          </select>
        </div>

        <button
          type="submit"
          disabled={isSavingBranches}
          className="mt-5 w-full rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
        >
          {isSavingBranches
            ? "Saving branches..."
            : "Save branch assignments"}
        </button>
      </form>

      <form
        onSubmit={handleWarehouseSubmit}
        className="rounded-xl border border-slate-200 bg-white p-5"
      >
        <div>
          <h4 className="font-bold text-slate-900">
            Manage warehouse assignments
          </h4>

          <p className="mt-1 text-sm text-slate-500">
            Warehouses are available only
            when their branches are selected.
          </p>
        </div>

        {warehouseErrors.warehouse_ids && (
          <p className="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {warehouseErrors.warehouse_ids}
          </p>
        )}

        {warehouseErrors.primary_warehouse_id && (
          <p className="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
            {
              warehouseErrors
                .primary_warehouse_id
            }
          </p>
        )}

        <div className="mt-5 max-h-80 space-y-3 overflow-y-auto pr-1">
          {selectedBranchIds.length ===
          0 ? (
            <p className="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
              Select and save at least one
              branch before assigning
              warehouses.
            </p>
          ) : availableWarehouses.length ===
            0 ? (
            <p className="text-sm text-slate-500">
              No active warehouses are
              available for the selected
              branches.
            </p>
          ) : (
            availableWarehouses.map(
              (warehouse) => {
                const isSelected =
                  selectedWarehouseIds.includes(
                    warehouse.id
                  );

                return (
                  <label
                    key={warehouse.id}
                    className={`flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition ${
                      isSelected
                        ? "border-emerald-300 bg-emerald-50"
                        : "border-slate-200 hover:bg-slate-50"
                    }`}
                  >
                    <input
                      type="checkbox"
                      checked={isSelected}
                      onChange={() =>
                        toggleWarehouse(
                          warehouse.id
                        )
                      }
                      disabled={
                        isSavingBranches ||
                        isSavingWarehouses
                      }
                      className="mt-1 h-4 w-4 accent-emerald-600"
                    />

                    <span className="min-w-0 flex-1">
                      <span className="block text-sm font-semibold text-slate-800">
                        {warehouse.name}
                      </span>

                      <span className="mt-1 block text-xs text-slate-500">
                        {
                          warehouse.branch
                            .name
                        }{" "}
                        · {warehouse.code}
                      </span>
                    </span>

                    {warehouse.is_primary && (
                      <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">
                        Company primary
                      </span>
                    )}
                  </label>
                );
              }
            )
          )}
        </div>

        <div className="mt-5">
          <label
            htmlFor="primary-warehouse"
            className="mb-2 block text-sm font-semibold text-slate-700"
          >
            Primary warehouse
          </label>

          <select
            id="primary-warehouse"
            value={
              primaryWarehouseId ?? ""
            }
            onChange={(event) =>
              handlePrimaryWarehouseChange(
                event.target.value
              )
            }
            disabled={
              selectedWarehouseIds.length ===
                0 || isSavingWarehouses
            }
            className="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 outline-none focus:border-emerald-600 disabled:cursor-not-allowed disabled:bg-slate-100"
          >
            <option value="">
              No primary warehouse
            </option>

            {availableWarehouses
              .filter((warehouse) =>
                selectedWarehouseIds.includes(
                  warehouse.id
                )
              )
              .map((warehouse) => (
                <option
                  key={warehouse.id}
                  value={warehouse.id}
                >
                  {warehouse.name}
                </option>
              ))}
          </select>
        </div>

        <button
          type="submit"
          disabled={
            isSavingWarehouses ||
            selectedBranchIds.length === 0
          }
          className="mt-5 w-full rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
        >
          {isSavingWarehouses
            ? "Saving warehouses..."
            : "Save warehouse assignments"}
        </button>
      </form>
    </div>
  );
}