#include <wiringPi.h>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <fcntl.h>
#include <errno.h>

#define Relay_Ch1 25
#define Relay_Ch2 28
#define Relay_Ch3 29

int main(int argc, char *argv[])
{
	char pathname[] = "/tmp/flashers.on";
	int delay_on = argc == 2 ? atoi(argv[1]) : 5;

	if(wiringPiSetup() == -1)
	{
		return 0;
	}//if

	int fd = open(pathname, O_CREAT | O_WRONLY | O_EXCL, S_IRUSR | S_IWUSR);
	if (fd < 0) {
		/* failure */
		if (errno == EEXIST) {
			/* the file already existed */
			return 0;
		}
	}

	pinMode(Relay_Ch3, OUTPUT);

	digitalWrite(Relay_Ch3, LOW);
	sleep(delay_on);
	digitalWrite(Relay_Ch3, HIGH);
	sleep(1);
	digitalWrite(Relay_Ch3, HIGH);

	remove(pathname);

	return 1;
}
