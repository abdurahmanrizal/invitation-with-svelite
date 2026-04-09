export type GuestReservation = {
  reservationCode: string;
  guestName: string;
  seatLabel: string;
  allowedGuests: number;
  phone: string;
  status: 'confirmed' | 'pending' | 'checked_in';
  checkedInAt?: string;
};
