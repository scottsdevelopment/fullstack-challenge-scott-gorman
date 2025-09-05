// types.ts

export type ID = number

export interface User {
  id: ID
  name: string
  email: string
  latitude: number
  longitude: number
  created_at: string
  updated_at: string
  email_verified_at?: string
}

export interface WeatherBrief {
  latitude?: number | null
  longitude?: number | null
  stationsUrl?: string | null
  conditionSummary?: string | null
  temperatureCelsius?: number | null
  temperatureFahrenheit?: number | null
  windSpeedKilometersPerHour?: number | null
  windSpeedMilesPerHour?: number | null
  relativeHumidityPercent?: number | null
  pressureMillibars?: number | null
  iconUrl?: string | null
  observedAtIso8601: string | null
  city?: string | null
  state?: string | null
}

export interface UsersIndexResponse {
  message?: string
  users: User[]
}

export interface UserShowResponse {
  user: User & { weather?: WeatherBrief | null }
}
