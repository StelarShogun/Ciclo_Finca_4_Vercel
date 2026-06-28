import { redirect } from "next/navigation";

// Home real (storefront) llega en un slice posterior; por ahora la raíz va al catálogo.
export default function RootPage() {
  redirect("/catalog");
}
