export type CartItem = {
    product_id: number;
    name: string;
    description: string | null;
    product_number: string;
    quantity: number;
    unit_price: string;
    line_total: string;
    product_url: string;
};

export type CartMethod = {
    id: string;
    label: string;
    description: string | null;
    selected: boolean;
    price?: string;
};

export type Cart = {
    items: CartItem[];
    count: number;
    is_empty: boolean;
    vat_rate: number;
    shipping_methods: CartMethod[];
    payment_methods: CartMethod[];
    selected_shipping_method: CartMethod | null;
    selected_payment_method: CartMethod | null;
    subtotal: string;
    shipping_total: string;
    total: string;
    net_total: string;
    vat_amount: string;
};
