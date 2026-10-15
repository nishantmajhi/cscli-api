/* Unit 8: Structure and Union
 * Structure within structure (nested structure). */
#include <stdio.h>

struct Date {
    int day, month, year;
};

struct Employee {
    char name[50];
    struct Date joiningDate; /* nested structure */
};

int main(void) {
    struct Employee e1 = {"Sita", {15, 6, 2079}};

    printf("Employee : %s\n", e1.name);
    printf("Joined on: %d/%d/%d\n",
           e1.joiningDate.day, e1.joiningDate.month, e1.joiningDate.year);

    return 0;
}
