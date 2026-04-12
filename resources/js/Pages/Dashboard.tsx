import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { ChartAreaInteractive } from "@/Components/chart-area-interactive"
import { SectionCards } from "@/Components/section-cards"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/Components/ui/card"

import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend
} from 'recharts';

// TODO: replace with real activity feed from the backend
const recentSales = [
  {
    name: "Olivia Martin",
    email: "olivia.martin@email.com",
    amount: "+$1,999.00",
  },
  {
    name: "Jackson Lee",
    email: "jackson.lee@email.com",
    amount: "+$39.00",
  },
  {
    name: "Isabella Nguyen",
    email: "isabella.nguyen@email.com",
    amount: "+$299.00",
  },
  {
    name: "William Kim",
    email: "will@email.com",
    amount: "+$99.00",
  },
  {
    name: "Sofia Davis",
    email: "sofia.davis@email.com",
    amount: "+$39.00",
  },
]

interface Props {
  emailHealth: {
    name: string;
    sent: number;
    delivered: number;
  }[];
}

export default function Dashboard({ emailHealth }: Props) {
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('verified') === '1') {
      toast.success('Email verified! Welcome aboard.');
      router.replace({ url: route('admin.dashboard'), preserveScroll: true });
    }
  }, []);

  return (
    <AuthenticatedLayout header="Dashboard">
      <Head title="Dashboard" />
      <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <SectionCards />
        </div>
        
        {/* Main Charts Row */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
          <div className="col-span-4">
            <ChartAreaInteractive />
          </div>
          <Card className="col-span-3">
            <CardHeader>
              <CardTitle>Recent Activity</CardTitle>
              <CardDescription>
                Recent site-wide sales and interactions.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-8">
                {recentSales.map((sale, index) => (
                  <div key={index} className="flex items-center">
                    <div className="space-y-1">
                      <p className="text-sm font-medium leading-none">{sale.name}</p>
                      <p className="text-sm text-muted-foreground">
                        {sale.email}
                      </p>
                    </div>
                    <div className="ml-auto font-medium">{sale.amount}</div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Email Health Row */}
        <div className="grid gap-4">
          <Card className="col-span-4">
            <CardHeader>
              <CardTitle>Email Delivery Health</CardTitle>
              <CardDescription>
                Outbound mail performance over the last 7 days (Sent vs Delivered).
              </CardDescription>
            </CardHeader>
            <CardContent className="h-[300px]">
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={emailHealth}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} />
                  <XAxis 
                    dataKey="name" 
                    stroke="#888888" 
                    fontSize={12} 
                    tickLine={false} 
                    axisLine={false} 
                  />
                  <YAxis 
                    stroke="#888888" 
                    fontSize={12} 
                    tickLine={false} 
                    axisLine={false} 
                    tickFormatter={(value) => `${value}`}
                  />
                  <Tooltip />
                  <Legend />
                  <Line 
                    type="monotone" 
                    dataKey="sent" 
                    name="Sent"
                    stroke="#8884d8" 
                    strokeWidth={2} 
                    dot={{ r: 4 }} 
                    activeDot={{ r: 6 }} 
                  />
                  <Line 
                    type="monotone" 
                    dataKey="delivered" 
                    name="Delivered"
                    stroke="#82ca9d" 
                    strokeWidth={2} 
                    dot={{ r: 4 }} 
                    activeDot={{ r: 6 }} 
                  />
                </LineChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
