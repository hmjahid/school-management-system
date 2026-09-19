import { success } from "@/lib/api-response";
import { eskoolfy } from "@/config/eskoolfy";

/** Health/parity probe: `GET /api/v1/ping` → `{success, message, data}`. */
export async function GET() {
  return success({
    status: "ok",
    variant: eskoolfy.variant,
    locale: eskoolfy.locale,
    timezone: eskoolfy.timezone,
    time: new Date().toISOString(),
  });
}
