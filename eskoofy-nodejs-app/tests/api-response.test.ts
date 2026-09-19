import { describe, expect, it } from "vitest";
import { isWebhookPath, paginated, success, error } from "@/lib/api-response";

describe("API envelope (mirrors StandardizeApiResponse)", () => {
  it("wraps payloads as {success, message, data}", async () => {
    const response = success({ id: 1 }, "Fetched.");
    expect(response.status).toBe(200);

    const body = await response.json();
    expect(body).toEqual({ success: true, message: "Fetched.", data: { id: 1 } });
  });

  it("hoists pagination under meta.pagination", async () => {
    const response = paginated([{ id: 1 }], { current_page: 2, per_page: 25, total: 60 });
    const body = await response.json();

    expect(body.data).toHaveLength(1);
    expect(body.meta.pagination).toEqual({
      current_page: 2,
      per_page: 25,
      total: 60,
      last_page: 3,
    });
  });

  it("uses a boolean success flag on errors", async () => {
    const body = await error("Nope", 422, null).json();
    expect(body).toEqual({ success: false, message: "Nope", data: null });
  });

  it("never rewraps gateway webhook/callback paths", () => {
    expect(isWebhookPath("/webhooks/stripe")).toBe(true);
    expect(isWebhookPath("/checkout/callback/paddle")).toBe(true);
    expect(isWebhookPath("/api/v1/licenses/activate")).toBe(false);
  });
});
