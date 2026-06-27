import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { ProductGallery } from '@/features/client/product/components/ProductGallery';
import { ProductPurchasePanel } from '@/features/client/product/components/ProductPurchasePanel';
import { ProductTabs } from '@/features/client/product/components/ProductTabs';
import type { ProductDetailPageProps } from '@/features/client/product/types';
import { ClientLayout } from '@/shared/components/layout/ClientLayout';
import type { InertiaSharedProps } from '@/shared/types/models';

import '../../../../../css/client/clients-page.css';
import '../../../../../css/client/product-badges.css';
import '../../../../../css/client/product-detail.css';

export default function ProductShowPage(props: ProductDetailPageProps) {
  const page = usePage<InertiaSharedProps>();
  const { auth, csrfToken } = page.props;
  const [activeTab, setActiveTab] = useState(props.tabs.defaultTab);
  const isAuthenticated = auth.client !== null;

  const productPath = `/product/${props.product.id}/${props.product.slug}`;

  return (
    <>
      <Head title={`${props.product.name} - Ciclo Finca 4`}>
        <link rel="canonical" href={props.seo.canonicalUrl} />
        <meta name="description" content={props.seo.description} />
        <meta name="robots" content={props.seo.robots} />
        <meta property="og:title" content={`${props.product.name} | Ciclo Finca 4`} />
        <meta property="og:description" content={props.seo.description} />
        <meta property="og:url" content={props.seo.canonicalUrl} />
        <meta property="og:type" content="product" />
        <meta property="og:image" content={props.seo.ogImage} />
        <meta name="twitter:card" content="summary_large_image" />
      </Head>
      <ClientLayout>
        <div className="product-detail-container product-detail-page">
          <div className="container">
            <nav className="breadcrumb product-detail-breadcrumb" aria-label="Ruta de navegación">
              <Link href="/">Inicio</Link>
              <span aria-hidden="true">/</span>
              <Link href="/catalog">Catálogo</Link>
              {props.taxonomy.parentCategory ? (
                <>
                  <span aria-hidden="true">/</span>
                  <Link href={props.taxonomy.parentCategory.url}>{props.taxonomy.parentCategory.name}</Link>
                </>
              ) : null}
              {props.taxonomy.subcategory ? (
                <>
                  <span aria-hidden="true">/</span>
                  <Link href={props.taxonomy.subcategory.url}>{props.taxonomy.subcategory.name}</Link>
                </>
              ) : null}
              <span aria-hidden="true">/</span>
              <span aria-current="page">{props.product.name}</span>
            </nav>

            <div className="product-detail-layout product-detail-hero">
              <ProductGallery product={props.product} />
              <ProductPurchasePanel
                authClient={auth.client}
                csrfToken={csrfToken}
                isNovelty={props.isNoveltyProduct}
                orderReservationHours={props.orderReservationHours}
                primaryBrand={props.primaryBrand}
                product={props.product}
                reviewAvg={props.reviews.averageStars ?? 0}
                reviewCount={props.reviews.totalCount}
                taxonomy={props.taxonomy}
                whatsappConsultUrl={props.whatsappConsultUrl}
              />
            </div>

            <ProductTabs
              activeTab={activeTab}
              authClient={auth.client}
              csrfToken={csrfToken}
              isAuthenticated={isAuthenticated}
              product={props.product}
              productPath={productPath}
              relatedProducts={props.relatedProducts}
              reviews={props.reviews}
              setActiveTab={setActiveTab}
              specs={props.specs}
              tabs={props.tabs}
            />
          </div>
        </div>
      </ClientLayout>
    </>
  );
}
