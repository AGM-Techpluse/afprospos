export type ReportFilters = {
    range: string;
    start_date: string;
    end_date: string;
    shop_id: number | null;
};

export type ShopOption = { id: number; name: string; sku_prefix_code?: string; is_warehouse?: boolean };

export type Metric = {
    label: string;
    value?: number;
    value_minor?: number;
};

export type BreakdownPoint = {
    label: string;
    value?: number;
    value_minor?: number;
    count?: number;
};

export type TrendPoint = {
    date: string;
    [key: string]: string | number;
};

