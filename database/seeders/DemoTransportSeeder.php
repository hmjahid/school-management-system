<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\TransportAssignment;
use App\Models\Student;
use Illuminate\Database\Seeder;

class DemoTransportSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            ['number' => 'DHAKA-METRO-B-12-3456', 'type' => 'bus', 'capacity' => 50, 'driver_name' => 'Shuttle Bus 1', 'driver_phone' => '01711111111', 'is_active' => true],
            ['number' => 'DHAKA-METRO-B-12-7890', 'type' => 'bus', 'capacity' => 45, 'driver_name' => 'Shuttle Bus 2', 'driver_phone' => '01722222222', 'is_active' => true],
            ['number' => 'DHAKA-METRO-B-11-2345', 'type' => 'microbus', 'capacity' => 14, 'driver_name' => 'Microbus 1', 'driver_phone' => '01733333333', 'is_active' => true],
            ['number' => 'DHAKA-METRO-B-12-5678', 'type' => 'bus', 'capacity' => 55, 'driver_name' => 'Shuttle Bus 3', 'driver_phone' => '01744444444', 'is_active' => true],
            ['number' => 'DHAKA-METRO-B-11-9012', 'type' => 'microbus', 'capacity' => 14, 'driver_name' => 'Microbus 2', 'driver_phone' => '01755555555', 'is_active' => false],
        ];

        foreach ($vehicles as $v) {
            Vehicle::create($v);
        }

        $routesData = [
            ['name' => 'Mirpur Route', 'code' => 'R-01'],
            ['name' => 'Uttara Route', 'code' => 'R-02'],
            ['name' => 'Gulshan Route', 'code' => 'R-03'],
            ['name' => 'Mohammadpur Route', 'code' => 'R-04'],
        ];

        foreach ($routesData as $r) {
            TransportRoute::create($r);
        }

        $stopsData = [
            'R-01' => ['Mirpur 10', 'Mirpur 1', 'Kallyanpur', 'Shyamoli', 'Technical', 'Gabtoli', 'School'],
            'R-02' => ['Uttara Sector 3', 'Uttara Sector 7', 'Airport', 'Khilkhet', 'Banonpara', 'School'],
            'R-03' => ['Gulshan 2', 'Gulshan 1', 'Banani', 'Mohakhali', 'Farmgate', 'School'],
            'R-04' => ['Mohammadpur Town Hall', 'Asadgate', 'Shyamoli', 'Technical', 'School'],
        ];

        foreach ($stopsData as $code => $stops) {
            $route = TransportRoute::where('code', $code)->first();
            if (!$route) continue;
            foreach ($stops as $order => $stopName) {
                TransportStop::create([
                    'route_id' => $route->id,
                    'name' => $stopName,
                    'sort' => $order + 1,
                    'pickup_time' => sprintf('%02d:%02d:00', 7 + intdiv($order * 15, 60), ($order * 15) % 60),
                ]);
            }
        }

        $students = Student::where('status', 'active')->take(30)->get();
        $routes = TransportRoute::all();
        foreach ($students as $student) {
            TransportAssignment::create([
                'student_id' => $student->id,
                'route_id' => $routes->random()->id,
                'effective_from' => now()->subMonths(rand(1, 6))->format('Y-m-d'),
            ]);
        }
    }
}
