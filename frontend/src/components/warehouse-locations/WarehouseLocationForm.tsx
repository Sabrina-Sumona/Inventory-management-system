"use client";

import {
  type FormEvent,
  useMemo,
  useState,
} from "react";

import type { Warehouse } from "@/types/warehouse";
import type {
  WarehouseLocation,
  WarehouseLocationPayload,
  WarehouseLocationType,
} from "@/types/warehouseLocation";

interface WarehouseLocationFormValues {
  warehouseId: string;
  parentId: string;
  name: string;
  code: string;
  type: WarehouseLocationType;
  barcode: string;
  capacity: string;
  description: string;
  isActive: boolean;
}

interface WarehouseLocationFormProps {
  location: WarehouseLocation | null;
  warehouses: Warehouse[];
  availableLocations: WarehouseLocation[];
  isSaving: boolean;
  errors: Record<string, string>;
  onCancel: () => void;
  onSubmit: (
    payload: WarehouseLocationPayload
  ) => Promise<void>;
}

const emptyForm: WarehouseLocationFormValues = {
  warehouseId: "",
  parentId: "",
  name: "",
  code: "",
  type: "zone",
  barcode: "",
  capacity: "",
  description: "",
  isActive: true,
};

const inputClassName =
  "h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500";

const selectClassName =
  "h-11 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500";

const textareaClassName =
  "w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500";

function locationToForm(
  location: WarehouseLocation | null
): WarehouseLocationFormValues {
  if (!location) {
    return {
      ...emptyForm,
    };
  }

  return {
    warehouseId: String(location.warehouse_id),
    parentId:
      location.parent_id === null
        ? ""
        : String(location.parent_id),
    name: location.name,
    code: location.code,
    type: location.type,
    barcode: location.barcode ?? "",
    capacity: location.capacity ?? "",
    description: location.description ?? "",
    isActive: location.is_active,
  };
}

function nullableString(
  value: string
): string | null {
  const trimmedValue = value.trim();

  return trimmedValue === ""
    ? null
    : trimmedValue;
}

function getRequiredParentType(
  type: WarehouseLocationType
): WarehouseLocationType | null {
  switch (type) {
    case "rack":
      return "zone";

    case "shelf":
      return "rack";

    case "bin":
      return "shelf";

    case "zone":
    default:
      return null;
  }
}

function formatType(
  type: WarehouseLocationType
): string {
  return (
    type.charAt(0).toUpperCase() +
    type.slice(1)
  );
}

export function WarehouseLocationForm({
  location,
  warehouses,
  availableLocations,
  isSaving,
  errors,
  onCancel,
  onSubmit,
}: WarehouseLocationFormProps) {
  const [form, setForm] =
    useState<WarehouseLocationFormValues>(
      locationToForm(location)
    );

  const selectedWarehouseId = Number(
    form.warehouseId
  );

  const requiredParentType =
    getRequiredParentType(form.type);

  const parentOptions = useMemo(() => {
    if (
      !Number.isInteger(selectedWarehouseId) ||
      selectedWarehouseId <= 0 ||
      requiredParentType === null
    ) {
      return [];
    }

    return availableLocations.filter(
      (availableLocation) =>
        availableLocation.warehouse_id ===
          selectedWarehouseId &&
        availableLocation.type ===
          requiredParentType &&
        availableLocation.is_active &&
        availableLocation.id !== location?.id
    );
  }, [
    availableLocations,
    location?.id,
    requiredParentType,
    selectedWarehouseId,
  ]);

  function updateField<
    Key extends keyof WarehouseLocationFormValues
  >(
    field: Key,
    value: WarehouseLocationFormValues[Key]
  ): void {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));
  }

  async function handleSubmit(
    event: FormEvent<HTMLFormElement>
  ): Promise<void> {
    event.preventDefault();

    const warehouseId = Number(
      form.warehouseId
    );

    if (
      !Number.isInteger(warehouseId) ||
      warehouseId <= 0
    ) {
      return;
    }

    const parentId =
      form.type === "zone" ||
      form.parentId === ""
        ? null
        : Number(form.parentId);

    if (
      form.type !== "zone" &&
      (
        parentId === null ||
        !Number.isInteger(parentId) ||
        parentId <= 0
      )
    ) {
      return;
    }

    const parsedCapacity =
      form.capacity.trim() === ""
        ? null
        : Number(form.capacity);

    await onSubmit({
      warehouse_id: warehouseId,
      parent_id: parentId,
      name: form.name.trim(),
      code: form.code
        .trim()
        .toUpperCase()
        .replace(/\s+/g, "-"),
      type: form.type,
      barcode: nullableString(form.barcode),
      capacity:
        parsedCapacity !== null &&
        Number.isFinite(parsedCapacity)
          ? parsedCapacity
          : null,
      description: nullableString(
        form.description
      ),
      is_active: form.isActive,
    });
  }

  return (
    <div className="fixed inset-0 z-[70] overflow-y-auto bg-slate-950/60 px-4 py-8">
      <div className="mx-auto w-full max-w-3xl rounded-2xl bg-white shadow-xl">
        <header className="flex items-start justify-between border-b border-slate-200 px-6 py-5">
          <div>
            <h2 className="text-xl font-bold text-slate-950">
              {location
                ? "Edit warehouse location"
                : "Create warehouse location"}
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              {location
                ? "Update the warehouse location and hierarchy."
                : "Add a zone, rack, shelf or bin inside a warehouse."}
            </p>
          </div>

          <button
            type="button"
            onClick={onCancel}
            disabled={isSaving}
            aria-label="Close warehouse location form"
            className="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
          >
            ✕
          </button>
        </header>

        <form
          onSubmit={handleSubmit}
          className="p-6"
        >
          <div className="grid gap-5 md:grid-cols-2">
            <div className="md:col-span-2">
              <label
                htmlFor="location-warehouse"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Warehouse
              </label>

              <select
                id="location-warehouse"
                required
                value={form.warehouseId}
                onChange={(event) => {
                  setForm((current) => ({
                    ...current,
                    warehouseId:
                      event.target.value,
                    parentId: "",
                  }));
                }}
                disabled={isSaving}
                className={selectClassName}
              >
                <option
                  value=""
                  disabled
                  className="text-slate-400"
                >
                  Select a warehouse
                </option>

                {warehouses.map((warehouse) => (
                  <option
                    key={warehouse.id}
                    value={warehouse.id}
                    className="text-slate-900"
                  >
                    {warehouse.name} (
                    {warehouse.code})
                  </option>
                ))}
              </select>

              {errors.warehouse_id && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.warehouse_id}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="location-type"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Location type
              </label>

              <select
                id="location-type"
                required
                value={form.type}
                onChange={(event) => {
                  const nextType =
                    event.target
                      .value as WarehouseLocationType;

                  setForm((current) => ({
                    ...current,
                    type: nextType,
                    parentId: "",
                  }));
                }}
                disabled={isSaving}
                className={selectClassName}
              >
                <option value="zone">
                  Zone
                </option>

                <option value="rack">
                  Rack
                </option>

                <option value="shelf">
                  Shelf
                </option>

                <option value="bin">
                  Bin
                </option>
              </select>

              {errors.type && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.type}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="location-parent"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Parent location
              </label>

              <select
                id="location-parent"
                required={form.type !== "zone"}
                value={form.parentId}
                onChange={(event) =>
                  updateField(
                    "parentId",
                    event.target.value
                  )
                }
                disabled={
                  isSaving ||
                  form.type === "zone" ||
                  form.warehouseId === ""
                }
                className={selectClassName}
              >
                <option value="">
                  {form.type === "zone"
                    ? "Root location"
                    : requiredParentType
                      ? `Select a ${formatType(
                          requiredParentType
                        ).toLowerCase()}`
                      : "Select a parent"}
                </option>

                {parentOptions.map(
                  (parent) => (
                    <option
                      key={parent.id}
                      value={parent.id}
                    >
                      {parent.name} (
                      {parent.code})
                    </option>
                  )
                )}
              </select>

              {errors.parent_id && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.parent_id}
                </p>
              )}

              {form.type !== "zone" &&
                form.warehouseId !== "" &&
                parentOptions.length === 0 && (
                  <p className="mt-2 text-sm text-amber-700">
                    No active{" "}
                    {requiredParentType} location is
                    available in this warehouse.
                  </p>
                )}
            </div>

            <div>
              <label
                htmlFor="location-name"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Location name
              </label>

              <input
                id="location-name"
                required
                maxLength={255}
                value={form.name}
                onChange={(event) =>
                  updateField(
                    "name",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className={inputClassName}
              />

              {errors.name && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.name}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="location-code"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Location code
              </label>

              <input
                id="location-code"
                required
                maxLength={50}
                placeholder="RACK-A2"
                value={form.code}
                onChange={(event) =>
                  updateField(
                    "code",
                    event.target.value.toUpperCase()
                  )
                }
                disabled={isSaving}
                className={`${inputClassName} uppercase`}
              />

              {errors.code && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.code}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="location-barcode"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Barcode
              </label>

              <input
                id="location-barcode"
                maxLength={100}
                value={form.barcode}
                onChange={(event) =>
                  updateField(
                    "barcode",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className={inputClassName}
              />

              {errors.barcode && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.barcode}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="location-capacity"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Capacity
              </label>

              <input
                id="location-capacity"
                type="number"
                min="0"
                step="0.001"
                value={form.capacity}
                onChange={(event) =>
                  updateField(
                    "capacity",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className={inputClassName}
              />

              {errors.capacity && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.capacity}
                </p>
              )}
            </div>

            <div className="flex items-center pt-7">
              <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input
                  type="checkbox"
                  checked={form.isActive}
                  onChange={(event) =>
                    updateField(
                      "isActive",
                      event.target.checked
                    )
                  }
                  disabled={isSaving}
                  className="h-4 w-4 rounded border-slate-300"
                />

                Active
              </label>
            </div>

            {errors.is_active && (
              <p className="text-sm text-red-600 md:col-span-2">
                {errors.is_active}
              </p>
            )}

            <div className="md:col-span-2">
              <label
                htmlFor="location-description"
                className="mb-2 block text-sm font-medium text-slate-700"
              >
                Description
              </label>

              <textarea
                id="location-description"
                rows={4}
                maxLength={2000}
                value={form.description}
                onChange={(event) =>
                  updateField(
                    "description",
                    event.target.value
                  )
                }
                disabled={isSaving}
                className={textareaClassName}
              />

              {errors.description && (
                <p className="mt-2 text-sm text-red-600">
                  {errors.description}
                </p>
              )}
            </div>
          </div>

          <footer className="mt-7 flex justify-end gap-3 border-t border-slate-200 pt-5">
            <button
              type="button"
              onClick={onCancel}
              disabled={isSaving}
              className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              Cancel
            </button>

            <button
              type="submit"
              disabled={
                isSaving ||
                warehouses.length === 0 ||
                form.warehouseId === "" ||
                (form.type !== "zone" &&
                  form.parentId === "")
              }
              className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-400"
            >
              {isSaving
                ? "Saving..."
                : location
                  ? "Save changes"
                  : "Create location"}
            </button>
          </footer>
        </form>
      </div>
    </div>
  );
}