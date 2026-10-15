/* Unit 9: Pointers
 * Array of pointers - here, an array of pointers to strings. */
#include <stdio.h>

int main(void) {
    const char *days[] = {"Sunday", "Monday", "Tuesday", "Wednesday",
                           "Thursday", "Friday", "Saturday"};

    for (int i = 0; i < 7; i++) {
        printf("%d: %s\n", i + 1, days[i]);
    }

    return 0;
}
