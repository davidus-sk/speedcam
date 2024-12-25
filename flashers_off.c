#include <wiringPi.h>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>

#define Relay_Ch1 25
#define Relay_Ch2 28
#define Relay_Ch3 29

int main(int argc, char *argv[])
{
	if(wiringPiSetup() == -1)
	{
		return 0;
	}//if

	pinMode(Relay_Ch3,OUTPUT);
	digitalWrite(Relay_Ch3, HIGH);

	return 1;
}
