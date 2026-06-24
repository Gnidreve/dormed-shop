export type CartItem = {
    product_id: number;
    name: string;
    description: string | null;
    product_number: string;
    quantity: number;
    unit_price: string;
    line_total: string;
    product_url: string;
    is_available: boolean;
};

export type CartMethod = {
    id: string;
    provider: string;
    label: string;
    description: string | null;
    selected: boolean;
    price?: string;
};

export type AddressData = {
    company: string;
    salutation: string;
    first_name: string;
    last_name: string;
    street: string;
    house_number: string;
    address_line2: string;
    zip: string;
    city: string;
    country: string;
    phone: string;
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
    shipping_address: AddressData;
    billing_address: AddressData | null;
};
