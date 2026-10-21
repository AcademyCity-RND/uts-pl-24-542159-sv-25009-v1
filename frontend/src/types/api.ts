export type UserRole = 'admin' | 'user'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  phone_number?: string | null
  created_at?: string
  updated_at?: string
}

// Sesuai skema database enum lost_items: 'lost' atau 'found'
export type ItemCategory = 'lost' | 'found'
// Sesuai skema database status lost_items
export type ItemStatus = 'pending' | 'approved' | 'rejected' | 'resolve_requested' | 'resolved'

export interface LostItem {
  id: number
  user_id: number
  item_name: string
  category: ItemCategory
  location: string
  current_storage: string
  contact_info?: string | null
  reporter_contact_info?: string | null
  chronology: string
  image_path?: string | null
  status: ItemStatus
  approved_at?: string | null
  resolved_at?: string | null
  expires_at?: string | null
  is_expired?: boolean
  remaining_seconds?: number
  user?: User
  created_at?: string
  updated_at?: string
}

// Sesuai skema database status item_reports: 'pending', 'accepted', 'rejected', 'closed'
export type ReportStatus = 'pending' | 'accepted' | 'rejected' | 'closed'

export interface ItemReport {
  id: number
  lost_item_id: number
  reporter_user_id: number
  message: string
  status: ReportStatus
  reporter?: {
    id: number
    name: string
    email: string
  }
  lost_item?: {
    id: number
    user_id: number
    item_name: string
    category: ItemCategory
    status: ItemStatus
    location: string
    image_path?: string | null
    approved_at?: string | null
  }
  created_at?: string
  updated_at?: string
}

// Struktur respons baku backend Laravel (tanpa wrapper properti success di root)
export interface ApiResponse<T> {
  message?: string
  data: T
}

export interface PaginatedResponse<T> {
  data: T[]
  links?: {
    first?: string
    last?: string
    prev?: string | null
    next?: string | null
  }
  meta?: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
}
