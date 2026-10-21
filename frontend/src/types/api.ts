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

export type ItemType = 'lost' | 'found'
export type ItemStatus = 'pending' | 'approved' | 'rejected' | 'resolve_requested' | 'resolved' | 'expired'
export type ItemCategory = 'Elektronik' | 'Dokumen / Kartu' | 'Aksesoris & Pakaian' | 'Kunci' | 'Lain-lain'

export interface LostItem {
  id: number
  user_id: number
  type: ItemType
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
  last_reposted_at?: string | null
  remaining_seconds?: number
  user?: User
  created_at?: string
  updated_at?: string
}

export type ReportStatus = 'pending' | 'accepted' | 'rejected' | 'closed'

export interface ItemReport {
  id: number
  lost_item_id: number
  user_id: number
  reporter_contact: string
  message: string
  status: ReportStatus
  user?: User
  lost_item?: LostItem
  created_at?: string
  updated_at?: string
}

export interface ApiResponse<T> {
  success: boolean
  message: string
  data: T
}

export interface PaginatedResponse<T> {
  success: boolean
  message: string
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
