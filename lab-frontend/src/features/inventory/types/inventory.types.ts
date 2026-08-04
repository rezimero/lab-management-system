import type {
  BranchSummary,
} from '../../commercial/types/commercial.types';

export type InventoryItemType =
  | 'reagent'
  | 'consumable'
  | 'general';

export interface InventoryCategory {
  uuid: string;
  code: string;
  name: string;
  description?: string | null;
  is_active: boolean;
  items_count?: number;
}

export interface InventoryUnit {
  uuid: string;
  code: string;
  name: string;
  symbol: string;
  decimal_places: number;
  is_active: boolean;
  items_count?: number;
}

export interface InventorySupplier {
  uuid: string;
  code: string;
  name: string;
  contact_name?: string | null;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  tax_identifier?: string | null;
  is_active: boolean;
  receipts_count?: number;
}

export interface InventoryBranchSetting {
  branch: Pick<
    BranchSummary,
    'uuid' | 'code' | 'name' | 'type' | 'is_active'
  >;
  reorder_level?: string | null;
  is_active: boolean;
}

export interface InventoryItem {
  uuid: string;
  sku: string;
  name: string;
  item_type: InventoryItemType;
  category?: Pick<InventoryCategory, 'uuid' | 'code' | 'name'>;
  unit?: Pick<InventoryUnit, 'uuid' | 'code' | 'name' | 'symbol' | 'decimal_places'>;
  manufacturer?: string | null;
  catalogue_number?: string | null;
  description?: string | null;
  tracks_lots: boolean;
  tracks_expiry: boolean;
  default_reorder_level: string;
  is_active: boolean;
  branches?: InventoryBranchSetting[];
}

export interface InventoryLot {
  uuid: string;
  internal_lot_code: string;
  external_lot_number?: string | null;
  batch_number?: string | null;
  manufactured_at?: string | null;
  expires_at?: string | null;
  is_expired?: boolean;
}

export interface InventoryStockBalance {
  branch: Pick<BranchSummary, 'uuid' | 'code' | 'name'>;
  item: InventoryItem;
  lot: InventoryLot;
  quantity_on_hand: string;
  last_movement_at?: string | null;
}

export interface InventoryMovement {
  uuid: string;
  movement_type: 'receipt' | 'adjustment_in' | 'adjustment_out' | 'consumption';
  quantity_delta: string;
  quantity_after: string;
  reason?: string | null;
  occurred_at?: string | null;
  branch?: Pick<BranchSummary, 'uuid' | 'code' | 'name'>;
  item?: {
    uuid: string;
    sku: string;
    name: string;
    unit_symbol?: string | null;
  };
  lot?: InventoryLot;
  performed_by?: {
    id: number;
    name: string;
    email?: string;
  } | null;
}

export interface InventoryReceipt {
  uuid: string;
  receipt_number: string;
  branch?: Pick<BranchSummary, 'uuid' | 'code' | 'name'>;
  supplier?: Pick<InventorySupplier, 'uuid' | 'code' | 'name'> | null;
  supplier_reference?: string | null;
  received_at?: string | null;
  notes?: string | null;
  lots_count?: number;
  lots?: Array<InventoryLot & {
    quantity_received: string;
    unit_cost?: string | null;
    currency: string;
    item: {
      uuid: string;
      sku: string;
      name: string;
      unit_symbol?: string | null;
    };
  }>;
  movements?: InventoryMovement[];
}

export interface InventoryAdjustment {
  uuid: string;
  adjustment_number: string;
  reason: string;
  notes?: string | null;
  adjusted_at?: string | null;
  branch?: Pick<BranchSummary, 'uuid' | 'code' | 'name'>;
  movements_count?: number;
  movements?: InventoryMovement[];
}

export interface InventoryConsumption {
  uuid: string;
  consumption_number: string;
  consumed_at?: string | null;
  notes?: string | null;
  branch?: Pick<BranchSummary, 'uuid' | 'code' | 'name'>;
  order_item?: {
    uuid: string;
    test_name: string;
    order_number?: string | null;
  };
  recorded_by?: {
    id: number;
    name: string;
  } | null;
  movements?: InventoryMovement[];
}

export interface InventoryAlertRow {
  branch: Pick<BranchSummary, 'uuid' | 'code' | 'name'>;
  item: InventoryItem;
  quantity_on_hand: string;
  reorder_level: string;
}

export interface InventoryAlerts {
  summary: {
    low_stock: number;
    out_of_stock: number;
    expiring_soon: number;
    expired: number;
  };
  low_stock: InventoryAlertRow[];
  out_of_stock: InventoryAlertRow[];
  expiring: InventoryStockBalance[];
}

export interface InventoryPage<T> {
  data: T[];
  meta?: {
    current_page: number;
    last_page: number;
    total: number;
  };
}

export interface InventoryItemPayload {
  sku: string;
  name: string;
  item_type: InventoryItemType;
  category_uuid: string;
  unit_uuid: string;
  manufacturer?: string;
  catalogue_number?: string;
  description?: string;
  tracks_lots: boolean;
  tracks_expiry: boolean;
  default_reorder_level: string;
  is_active: boolean;
  branches: Array<{
    branch_uuid: string;
    reorder_level?: string;
    is_active: boolean;
  }>;
}

export interface InventoryReceiptPayload {
  branch_uuid: string;
  supplier_uuid?: string;
  supplier_reference?: string;
  received_at: string;
  notes?: string;
  lines: Array<{
    item_uuid: string;
    external_lot_number?: string;
    batch_number?: string;
    manufactured_at?: string;
    expires_at?: string;
    quantity: string;
    unit_cost?: string;
    currency?: string;
  }>;
}

export interface InventoryAdjustmentPayload {
  branch_uuid: string;
  reason: string;
  adjusted_at: string;
  notes?: string;
  lines: Array<{
    lot_uuid: string;
    direction: 'in' | 'out';
    quantity: string;
  }>;
}

export interface InventoryConsumptionContext {
  order_item: {
    uuid: string;
    test_name: string;
    status: string;
  };
  branch: Pick<BranchSummary, 'uuid' | 'code' | 'name'>;
  suggestions: Array<{
    quantity: string;
    notes?: string | null;
    item: InventoryItem;
  }>;
  available_lots: InventoryStockBalance[];
  consumptions: InventoryConsumption[];
}

export interface InventoryConsumptionPayload {
  consumed_at: string;
  notes?: string;
  lines: Array<{
    lot_uuid: string;
    quantity: string;
  }>;
}

export interface LabTestInventoryRequirementContext {
  lab_test: {
    uuid: string;
    id: number;
    name: string;
    category?: string | null;
  };
  requirements: Array<{
    quantity: string;
    is_active: boolean;
    notes?: string | null;
    item: InventoryItem;
  }>;
}
