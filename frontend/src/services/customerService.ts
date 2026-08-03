import { api } from "@/lib/api";

import type {
  Customer,
  CustomerContact,
  CustomerContactListResponse,
  CustomerContactPayload,
  CustomerContactQuery,
  CustomerContactResponse,
  CustomerContactUpdatePayload,
  CustomerFinancialSetting,
  CustomerFinancialSettingListResponse,
  CustomerFinancialSettingPayload,
  CustomerFinancialSettingQuery,
  CustomerFinancialSettingResponse,
  CustomerFinancialSettingUpdatePayload,
  CustomerListResponse,
  CustomerPayload,
  CustomerQuery,
  CustomerResponse,
} from "@/types/customer";

type QueryValue =
  | string
  | number
  | boolean;

function cleanQuery<T extends object>(
  query: T
): Record<string, QueryValue> {
  return Object.fromEntries(
    Object.entries(query).filter(
      ([, value]) =>
        value !== undefined &&
        value !== null &&
        value !== ""
    )
  ) as Record<string, QueryValue>;
}

async function ensureCsrfCookie(): Promise<void> {
  await api.get("/sanctum/csrf-cookie");
}

export const customerService = {
  async getCustomers(
    query: CustomerQuery = {}
  ): Promise<CustomerListResponse["data"]> {
    const response =
      await api.get<CustomerListResponse>(
        "/api/customers",
        {
          params: cleanQuery(query),
        }
      );

    return response.data.data;
  },

  async getCustomer(
    customerId: number
  ): Promise<Customer> {
    const response =
      await api.get<CustomerResponse>(
        `/api/customers/${customerId}`
      );

    return response.data.data.customer;
  },

  async createCustomer(
    payload: CustomerPayload
  ): Promise<Customer> {
    await ensureCsrfCookie();

    const response =
      await api.post<CustomerResponse>(
        "/api/customers",
        payload
      );

    return response.data.data.customer;
  },

  async updateCustomer(
    customerId: number,
    payload: Partial<CustomerPayload>
  ): Promise<Customer> {
    await ensureCsrfCookie();

    const response =
      await api.patch<CustomerResponse>(
        `/api/customers/${customerId}`,
        payload
      );

    return response.data.data.customer;
  },

  async deleteCustomer(
    customerId: number
  ): Promise<void> {
    await ensureCsrfCookie();

    await api.delete(
      `/api/customers/${customerId}`
    );
  },

  async restoreCustomer(
    customerId: number
  ): Promise<Customer> {
    await ensureCsrfCookie();

    const response =
      await api.post<CustomerResponse>(
        `/api/customers/${customerId}/restore`
      );

    return response.data.data.customer;
  },

  async getCustomerContacts(
    query: CustomerContactQuery = {}
  ): Promise<
    CustomerContactListResponse["data"]
  > {
    const response =
      await api.get<CustomerContactListResponse>(
        "/api/customer-contacts",
        {
          params: cleanQuery(query),
        }
      );

    return response.data.data;
  },

  async getCustomerContact(
    customerContactId: number
  ): Promise<CustomerContact> {
    const response =
      await api.get<CustomerContactResponse>(
        `/api/customer-contacts/${customerContactId}`
      );

    return response.data.data
      .customer_contact;
  },

  async createCustomerContact(
    payload: CustomerContactPayload
  ): Promise<CustomerContact> {
    await ensureCsrfCookie();

    const response =
      await api.post<CustomerContactResponse>(
        "/api/customer-contacts",
        payload
      );

    return response.data.data
      .customer_contact;
  },

  async updateCustomerContact(
    customerContactId: number,
    payload: CustomerContactUpdatePayload
  ): Promise<CustomerContact> {
    await ensureCsrfCookie();

    const response =
      await api.patch<CustomerContactResponse>(
        `/api/customer-contacts/${customerContactId}`,
        payload
      );

    return response.data.data
      .customer_contact;
  },

  async deleteCustomerContact(
    customerContactId: number
  ): Promise<void> {
    await ensureCsrfCookie();

    await api.delete(
      `/api/customer-contacts/${customerContactId}`
    );
  },

  async restoreCustomerContact(
    customerContactId: number
  ): Promise<CustomerContact> {
    await ensureCsrfCookie();

    const response =
      await api.post<CustomerContactResponse>(
        `/api/customer-contacts/${customerContactId}/restore`
      );

    return response.data.data
      .customer_contact;
  },

  async getCustomerFinancialSettings(
    query: CustomerFinancialSettingQuery = {}
  ): Promise<
    CustomerFinancialSettingListResponse["data"]
  > {
    const response =
      await api.get<CustomerFinancialSettingListResponse>(
        "/api/customer-financial-settings",
        {
          params: cleanQuery(query),
        }
      );

    return response.data.data;
  },

  async getCustomerFinancialSetting(
    customerFinancialSettingId: number
  ): Promise<CustomerFinancialSetting> {
    const response =
      await api.get<CustomerFinancialSettingResponse>(
        `/api/customer-financial-settings/${customerFinancialSettingId}`
      );

    return response.data.data
      .customer_financial_setting;
  },

  async createCustomerFinancialSetting(
    payload: CustomerFinancialSettingPayload
  ): Promise<CustomerFinancialSetting> {
    await ensureCsrfCookie();

    const response =
      await api.post<CustomerFinancialSettingResponse>(
        "/api/customer-financial-settings",
        payload
      );

    return response.data.data
      .customer_financial_setting;
  },

  async updateCustomerFinancialSetting(
    customerFinancialSettingId: number,
    payload: CustomerFinancialSettingUpdatePayload
  ): Promise<CustomerFinancialSetting> {
    await ensureCsrfCookie();

    const response =
      await api.patch<CustomerFinancialSettingResponse>(
        `/api/customer-financial-settings/${customerFinancialSettingId}`,
        payload
      );

    return response.data.data
      .customer_financial_setting;
  },

  async deleteCustomerFinancialSetting(
    customerFinancialSettingId: number
  ): Promise<void> {
    await ensureCsrfCookie();

    await api.delete(
      `/api/customer-financial-settings/${customerFinancialSettingId}`
    );
  },
};