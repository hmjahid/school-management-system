/**
 * Demo transport mirror — exact rows from eskoofy-laravel-app/database/seeders/DemoTransportSeeder.php.
 */
export interface DemoVehicleRow {
  name: string;
  code: string;
  type: string;
  number: string;
  driverName: string;
  driverPhone: string;
  capacity: number;
  isActive: boolean;
}

export const demoVehicles: readonly DemoVehicleRow[] = [
  { name: "Shuttle Bus 1", code: "BUS-001", type: "bus", number: "DHAKA-METRO-B-12-3456", driverName: "Driver 1", driverPhone: "01711111111", capacity: 50, isActive: true },
  { name: "Shuttle Bus 2", code: "BUS-002", type: "bus", number: "DHAKA-METRO-B-12-7890", driverName: "Driver 2", driverPhone: "01722222222", capacity: 45, isActive: true },
  { name: "Microbus 1", code: "MB-001", type: "microbus", number: "DHAKA-METRO-B-11-2345", driverName: "Driver 3", driverPhone: "01733333333", capacity: 14, isActive: true },
  { name: "Shuttle Bus 3", code: "BUS-003", type: "bus", number: "DHAKA-METRO-B-12-5678", driverName: "Driver 4", driverPhone: "01744444444", capacity: 55, isActive: true },
  { name: "Microbus 2", code: "MB-002", type: "microbus", number: "DHAKA-METRO-B-11-9012", driverName: "Driver 5", driverPhone: "01755555555", capacity: 14, isActive: true },
];
