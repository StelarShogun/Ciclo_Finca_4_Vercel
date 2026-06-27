import type { ReactNode } from 'react';

export type TabItem = {
  id: string;
  label: ReactNode;
  disabled?: boolean;
  count?: number;
};

type TabsProps = {
  tabs: TabItem[];
  activeTab: string;
  onChange: (tabId: string) => void;
  ariaLabel: string;
  className?: string;
  buttonClassName?: string;
};

export function Tabs({
  activeTab,
  ariaLabel,
  buttonClassName = 'product-detail-tabs__btn',
  className = 'product-detail-tabs__nav',
  onChange,
  tabs,
}: TabsProps) {
  return (
    <div className={className} role="tablist" aria-label={ariaLabel}>
      {tabs.map((tab) => (
        <button
          key={tab.id}
          type="button"
          role="tab"
          className={`${buttonClassName}${activeTab === tab.id ? ' is-active' : ''}`.trim()}
          data-tab={tab.id}
          aria-selected={activeTab === tab.id}
          disabled={tab.disabled}
          onClick={() => onChange(tab.id)}
        >
          {tab.label}
          {tab.count && tab.count > 0 ? <span className="product-detail-tabs__count">{tab.count}</span> : null}
        </button>
      ))}
    </div>
  );
}
