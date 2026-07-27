import { ResetPasswordForm } from "@/components/auth/ResetPasswordForm";

type SearchParamValue =
  | string
  | string[]
  | undefined;

interface ResetPasswordTokenPageProps {
  params: Promise<{
    token: string;
  }>;

  searchParams: Promise<{
    email?: SearchParamValue;
  }>;
}

function firstValue(
  value: SearchParamValue
): string {
  if (Array.isArray(value)) {
    return value[0] ?? "";
  }

  return value ?? "";
}

export default async function ResetPasswordTokenPage({
  params,
  searchParams,
}: ResetPasswordTokenPageProps) {
  const routeParams = await params;
  const queryParams = await searchParams;

  return (
    <ResetPasswordForm
      initialToken={routeParams.token}
      initialEmail={firstValue(
        queryParams.email
      )}
    />
  );
}