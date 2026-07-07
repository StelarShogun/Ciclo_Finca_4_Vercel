export type OrderAction = "view" | "mark-ready" | "complete" | "cancel" | "invoice";

export function orderActionsForStatus(status: string): OrderAction[] {
  const actions: OrderAction[] = ["view"];
  if (status === "pending") actions.push("mark-ready");
  if (status === "ready_to_pickup") actions.push("complete");
  if (status === "pending" || status === "ready_to_pickup") actions.push("cancel");
  if (status === "completed") actions.push("invoice");
  return actions;
}
