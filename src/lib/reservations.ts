import type { GuestReservation } from '$lib/types';

export const reservations: GuestReservation[] = [
  {
    reservationCode: 'UMR-ARIF-001',
    guestName: 'Arif Rahman',
    seatLabel: 'Table A · Seat 03',
    allowedGuests: 2,
    phone: '+62 812-1111-2222',
    status: 'confirmed'
  },
  {
    reservationCode: 'UMR-NISA-002',
    guestName: 'Nisa Azzahra',
    seatLabel: 'Table B · Seat 05',
    allowedGuests: 4,
    phone: '+62 813-3333-4444',
    status: 'confirmed'
  },
  {
    reservationCode: 'UMR-FARIS-003',
    guestName: 'Faris Maulana',
    seatLabel: 'Table C · Seat 01',
    allowedGuests: 1,
    phone: '+62 815-5555-6666',
    status: 'pending'
  }
];

export function getReservationByCode(reservationCode: string) {
  return reservations.find(
    (item) => item.reservationCode.toLowerCase() === reservationCode.toLowerCase()
  );
}

export function updateReservationStatus(
  reservationCode: string,
  status: 'confirmed' | 'pending' | 'checked_in'
): GuestReservation {
  const reservation = getReservationByCode(reservationCode);

  if (!reservation) {
    throw new Error('Reservation not found');
  }

  reservation.status = status;
  if (status === 'checked_in') {
    reservation.checkedInAt = new Date().toISOString();
  }

  return reservation;
}
